<?php

namespace app\models;

use Yii;
use yii\db\Query;

/**
 * Сервис для автоматического формирования заказов на основе ИИ
 * Анализирует историю заказов и формирует рекомендации
 */
class OrderRecommendation
{
    private $storeId;
    private $userId;
    private $daysHistory = 30; // Количество дней для анализа истории

    public function __construct($storeId, $userId)
    {
        $this->storeId = $storeId;
        $this->userId = $userId;
    }

    /**
     * Получить историю заказов для анализа
     *
     * @return array
     */
    public function getOrderHistory()
    {
        $dateFrom = date('Y-m-d', strtotime("-{$this->daysHistory} days"));

        $sql = "
            SELECT
                o.id as order_id,
                o.date as order_date,
                DAYOFWEEK(o.date) as day_of_week,
                p.id as product_id,
                p.name as product_name,
                p.mainUnit as unit,
                oi.quantity
            FROM orders o
            INNER JOIN order_items oi ON oi.orderId = o.id AND oi.deleted_at IS NULL
            INNER JOIN products p ON p.id = oi.productId
            WHERE o.storeId = :storeId
              AND o.date >= :dateFrom
              AND o.state IN (1, 2)
              AND o.deleted_at IS NULL
            ORDER BY o.date DESC, p.name ASC
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':storeId', $this->storeId)
            ->bindValue(':dateFrom', $dateFrom)
            ->queryAll();
    }

    /**
     * Получить статистику по продуктам
     *
     * @return array
     */
    public function getProductStats()
    {
        $dateFrom = date('Y-m-d', strtotime("-{$this->daysHistory} days"));

        $sql = "
            SELECT
                p.id as product_id,
                p.name as product_name,
                p.mainUnit as unit,
                COUNT(DISTINCT o.id) as order_count,
                AVG(oi.quantity) as avg_quantity,
                MIN(oi.quantity) as min_quantity,
                MAX(oi.quantity) as max_quantity,
                SUM(oi.quantity) as total_quantity
            FROM orders o
            INNER JOIN order_items oi ON oi.orderId = o.id AND oi.deleted_at IS NULL
            INNER JOIN products p ON p.id = oi.productId
            WHERE o.storeId = :storeId
              AND o.date >= :dateFrom
              AND o.state IN (1, 2)
              AND o.deleted_at IS NULL
            GROUP BY p.id, p.name, p.mainUnit
            HAVING order_count >= 2
            ORDER BY order_count DESC, avg_quantity DESC
        ";

        $result = Yii::$app->db->createCommand($sql)
            ->bindValue(':storeId', $this->storeId)
            ->bindValue(':dateFrom', $dateFrom)
            ->queryAll();

        Yii::info("getProductStats: storeId={$this->storeId}, dateFrom={$dateFrom}, products found: " . count($result), 'order-recommendation');

        // Log product IDs for debugging
        $productIds = array_column($result, 'product_id');
        Yii::info("getProductStats: product IDs: " . implode(', ', array_slice($productIds, 0, 10)) . "...", 'order-recommendation');

        return $result;
    }

    /**
     * Получить список доступных продуктов для пользователя
     *
     * @return array
     */
    public function getAvailableProducts()
    {
        $sql = "
            SELECT
                p.id,
                p.name,
                p.mainUnit,
                p.price,
                pg.name as group_name
            FROM products p
            INNER JOIN user_categories uc ON uc.category_id = p.id AND uc.user_id = :userId
            LEFT JOIN product_groups_link pgl ON pgl.productId = p.id
            LEFT JOIN product_groups pg ON pg.id = pgl.productGroupId
            WHERE p.productType != '' AND p.productType IS NOT NULL
            ORDER BY pg.name, p.name
        ";

        return Yii::$app->db->createCommand($sql)
            ->bindValue(':userId', $this->userId)
            ->queryAll();
    }

    /**
     * Получить название магазина
     *
     * @return string
     */
    public function getStoreName()
    {
        $store = Stores::findOne($this->storeId);
        return $store ? $store->name : 'Неизвестный филиал';
    }

    /**
     * Сформировать данные для отправки в Claude API
     *
     * @return array
     */
    public function prepareDataForAI()
    {
        $stats = $this->getProductStats();
        $history = $this->getOrderHistory();
        $products = $this->getAvailableProducts();
        $storeName = $this->getStoreName();

        // Группируем историю по дням недели
        $historyByDayOfWeek = [];
        foreach ($history as $item) {
            $dow = $item['day_of_week'];
            if (!isset($historyByDayOfWeek[$dow])) {
                $historyByDayOfWeek[$dow] = [];
            }
            $productId = $item['product_id'];
            if (!isset($historyByDayOfWeek[$dow][$productId])) {
                $historyByDayOfWeek[$dow][$productId] = [
                    'name' => $item['product_name'],
                    'quantities' => []
                ];
            }
            $historyByDayOfWeek[$dow][$productId]['quantities'][] = $item['quantity'];
        }

        return [
            'store_name' => $storeName,
            'target_day_of_week' => date('N', strtotime('+1 day')), // Следующий день
            'target_date' => date('Y-m-d', strtotime('+1 day')),
            'product_stats' => $stats,
            'history_by_day' => $historyByDayOfWeek,
            'available_products' => $products
        ];
    }

    /**
     * Сформировать промпт для Claude API
     *
     * @return array ['system' => ..., 'user' => ...]
     */
    public function buildPrompt()
    {
        $data = $this->prepareDataForAI();

        $systemPrompt = <<<PROMPT
Ты - ассистент для ресторана, который помогает формировать заказы продуктов.
Твоя задача - проанализировать историю заказов и предложить оптимальный заказ на завтра.

Правила:
1. Анализируй средние количества для каждого продукта
2. Учитывай день недели (выходные обычно требуют больше продуктов)
3. Рекомендуй только те продукты, которые заказывались регулярно (минимум 2 раза за месяц)
4. Округляй количества до разумных значений
5. Отвечай ТОЛЬКО в формате JSON
6. ВАЖНО: Ограничь список максимум 30 продуктами (самые важные/частые)
7. Поле reason должно быть коротким (до 30 символов)
8. КРИТИЧЕСКИ ВАЖНО: Используй ТОЛЬКО те product_id, которые указаны в квадратных скобках в данных. НЕ выдумывай ID!

Формат ответа (строго JSON, без markdown):
{
  "recommendations": [
    {
      "product_id": "uuid-из-данных",
      "product_name": "Название",
      "quantity": 10,
      "unit": "кг",
      "reason": "Коротко"
    }
  ],
  "summary": "Краткое описание"
}
PROMPT;

        // Формируем краткую статистику для экономии токенов
        // ВАЖНО: включаем product_id чтобы Claude использовал реальные ID из базы
        $statsText = "Статистика заказов за {$this->daysHistory} дней:\n";
        foreach (array_slice($data['product_stats'], 0, 50) as $stat) { // Ограничиваем до 50 продуктов
            $avgQty = round($stat['avg_quantity'], 2);
            $statsText .= "- [{$stat['product_id']}] {$stat['product_name']} ({$stat['unit']}): среднее {$avgQty}, заказов {$stat['order_count']}\n";
        }

        $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
        $targetDayName = $dayNames[$data['target_day_of_week']] ?? '';

        $userMessage = <<<MSG
Филиал: {$data['store_name']}
Дата заказа: {$data['target_date']} ({$targetDayName})

{$statsText}

Сформируй рекомендуемый заказ на указанную дату. Включи только продукты с регулярными заказами.
MSG;

        return [
            'system' => $systemPrompt,
            'user' => $userMessage
        ];
    }

    /**
     * Получить рекомендации от Claude API
     *
     * @return array|null Возвращает массив с ключами:
     *   - recommendations: массив рекомендаций
     *   - summary: краткое описание
     *   - usage: статистика использования токенов
     */
    public function getRecommendations()
    {
        try {
            $claude = new ClaudeService();
            $prompt = $this->buildPrompt();

            Yii::info("Sending request to Claude API", 'order-recommendation');
            Yii::info("System prompt length: " . strlen($prompt['system']), 'order-recommendation');
            Yii::info("User prompt length: " . strlen($prompt['user']), 'order-recommendation');

            $response = $claude->sendMessage(
                $prompt['system'],
                $prompt['user'],
                4096
            );

            Yii::info("Claude API response: " . json_encode($response), 'order-recommendation');

            if (!$response) {
                Yii::error("Claude API returned empty response", 'order-recommendation');
                return null;
            }

            $text = $claude->extractText($response);
            Yii::info("Extracted text: " . $text, 'order-recommendation');

            if (!$text) {
                Yii::error("Could not extract text from Claude response: " . json_encode($response), 'order-recommendation');
                return null;
            }

            // Парсим JSON из ответа
            $jsonText = $this->extractJson($text);
            Yii::info("Extracted JSON: " . $jsonText, 'order-recommendation');

            $recommendations = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Yii::error("Failed to parse JSON. Error: " . json_last_error_msg() . ". Text: " . $text, 'order-recommendation');
                return null;
            }

            // Добавляем статистику использования токенов
            $recommendations['usage'] = $claude->getUsageStats();

            Yii::info("Successfully parsed recommendations: " . count($recommendations['recommendations'] ?? []) . " items", 'order-recommendation');

            return $recommendations;

        } catch (\Exception $e) {
            Yii::error("OrderRecommendation error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), 'order-recommendation');
            return null;
        }
    }

    /**
     * Извлечь JSON из текста ответа
     *
     * @param string $text
     * @return string
     */
    private function extractJson($text)
    {
        // Убираем markdown code blocks если есть
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/', '', $text);

        // Ищем JSON объект
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            return $matches[0];
        }

        return $text;
    }

    /**
     * Установить количество дней для анализа
     *
     * @param int $days
     * @return $this
     */
    public function setDaysHistory($days)
    {
        $this->daysHistory = max(7, min(90, $days));
        return $this;
    }
}
