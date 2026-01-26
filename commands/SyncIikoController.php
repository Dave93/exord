<?php

namespace app\commands;

use app\models\Departments;
use app\models\Groups;
use app\models\Iiko;
use app\models\Products;
use app\models\Settings;
use app\models\Stores;
use app\models\Suppliers;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\Json;

/**
 * Синхронизация данных с iiko
 */
class SyncIikoController extends Controller
{
    private $baseUrl;
    private $token;

    /**
     * @var int количество дней для синхронизации цен (по умолчанию 1)
     */
    public $days = 1;

    /**
     * @var bool подробный вывод
     */
    public $verbose = false;

    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'days',
            'verbose',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
            'd' => 'days',
        ]);
    }

    /**
     * Полная синхронизация данных с iiko
     *
     * Синхронизирует: подразделения, поставщиков, склады, продукты, группы и входящие цены.
     *
     * Использование:
     *   php yii sync-iiko/index
     *   php yii sync-iiko/index --days=7
     *   php yii sync-iiko/index -v (подробный вывод)
     *
     * @return int код завершения
     */
    public function actionIndex()
    {
        set_time_limit(0);
        error_reporting(E_ALL);
        ini_set('memory_limit', -1);
        ini_set('display_errors', 1);

        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════════════╗\n", Console::FG_CYAN);
        $this->stdout("║           СИНХРОНИЗАЦИЯ ДАННЫХ С IIKO                        ║\n", Console::FG_CYAN);
        $this->stdout("╚══════════════════════════════════════════════════════════════╝\n", Console::FG_CYAN);
        $this->stdout("\n");

        $startTime = microtime(true);

        // Авторизация
        $this->stdout("[1/7] Авторизация в iiko... ", Console::FG_YELLOW);
        $iiko = new Iiko();
        if (!$iiko->auth()) {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->stderr("Не удалось авторизоваться в iiko\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);

        // Синхронизация подразделений
        $this->stdout("[2/7] Синхронизация подразделений (departments)... ", Console::FG_YELLOW);
        $countBefore = Departments::find()->count();
        $result = $iiko->departments();
        $countAfter = Departments::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация поставщиков
        $this->stdout("[3/7] Синхронизация поставщиков (suppliers)... ", Console::FG_YELLOW);
        $countBefore = Suppliers::find()->count();
        $result = $iiko->suppliers();
        $countAfter = Suppliers::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация складов
        $this->stdout("[4/7] Синхронизация складов (stores)... ", Console::FG_YELLOW);
        $countBefore = Stores::find()->count();
        $result = $iiko->stores();
        $countAfter = Stores::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация продуктов
        $this->stdout("[5/7] Синхронизация продуктов (products)... ", Console::FG_YELLOW);
        $countBefore = Products::find()->count();
        $result = $iiko->products();
        $countAfter = Products::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация групп
        $this->stdout("[6/7] Синхронизация групп (groups)... ", Console::FG_YELLOW);
        $countBefore = Groups::find()->count();
        $result = $iiko->groups();
        $countAfter = Groups::find()->count();
        $this->printSyncResult($result, $countBefore, $countAfter);

        // Синхронизация входящих цен
        $now = date('Y-m-d');
        $start = date('Y-m-d', strtotime($now . " -{$this->days} days"));
        $this->stdout("[7/7] Синхронизация входящих цен ({$start} - {$now})... ", Console::FG_YELLOW);
        $result = $iiko->incomingPrices($start, $now);
        if ($result === true) {
            $this->stdout("OK\n", Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }

        // Итоговая статистика
        $this->stdout("\n");
        $this->stdout("Статистика таблиц:\n", Console::FG_CYAN, Console::BOLD);
        $this->printTableStats('departments', Departments::find()->count());
        $this->printTableStats('suppliers', Suppliers::find()->count());
        $this->printTableStats('stores', Stores::find()->count());
        $this->printTableStats('products', Products::find()->count());
        $this->printTableStats('groups', Groups::find()->count());

        // Итоги
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->stdout("\n");
        $this->stdout("══════════════════════════════════════════════════════════════\n", Console::FG_CYAN);
        $this->stdout("Синхронизация завершена за {$duration} сек.\n", Console::FG_GREEN, Console::BOLD);
        $this->stdout("══════════════════════════════════════════════════════════════\n", Console::FG_CYAN);
        $this->stdout("\n");

        return ExitCode::OK;
    }

    /**
     * Вывод результата синхронизации с количеством записей
     * @param mixed $result
     * @param int $countBefore
     * @param int $countAfter
     */
    private function printSyncResult($result, $countBefore, $countAfter)
    {
        if ($result === true) {
            if ($countAfter == 0) {
                $this->stdout("ПУСТО", Console::FG_RED, Console::BOLD);
                $this->stdout(" (было: {$countBefore}, стало: 0)\n", Console::FG_RED);
            } else {
                $this->stdout("OK", Console::FG_GREEN, Console::BOLD);
                $diff = $countAfter - $countBefore;
                $diffStr = $diff >= 0 ? "+{$diff}" : "{$diff}";
                $this->stdout(" ({$countAfter} записей, {$diffStr})\n");
            }
        } else {
            $this->stdout("ОШИБКА\n", Console::FG_RED, Console::BOLD);
            $this->printErrors($result);
        }
    }

    /**
     * Вывод статистики таблицы
     * @param string $table
     * @param int $count
     */
    private function printTableStats($table, $count)
    {
        $paddedTable = str_pad($table, 15);
        if ($count == 0) {
            $this->stdout("  {$paddedTable}: ", Console::BOLD);
            $this->stdout("{$count} (ПУСТО!)\n", Console::FG_RED, Console::BOLD);
        } else {
            $this->stdout("  {$paddedTable}: {$count}\n", Console::BOLD);
        }
    }

    /**
     * Вывод ошибок
     * @param mixed $errors
     */
    private function printErrors($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $field => $error) {
                $this->stderr("  - {$field}: {$error}\n", Console::FG_RED);
            }
        } elseif (is_string($errors)) {
            $this->stderr("  - {$errors}\n", Console::FG_RED);
        }
    }

    /**
     * Тестирование соединения с iiko и вывод сырых данных
     *
     * Использование:
     *   php yii sync-iiko/test
     *   php yii sync-iiko/test products
     *   php yii sync-iiko/test departments
     *
     * @param string $endpoint Эндпоинт для тестирования (products, departments, suppliers, stores, groups)
     * @return int
     */
    public function actionTest($endpoint = 'products')
    {
        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════════════╗\n", Console::FG_CYAN);
        $this->stdout("║           ТЕСТ СОЕДИНЕНИЯ С IIKO                             ║\n", Console::FG_CYAN);
        $this->stdout("╚══════════════════════════════════════════════════════════════╝\n", Console::FG_CYAN);
        $this->stdout("\n");

        // Получаем настройки
        $this->baseUrl = Settings::getValue("iiko-server");
        $login = Settings::getValue("iiko-login");
        $password = Settings::getValue("iiko-password");

        $this->stdout("Настройки:\n", Console::FG_YELLOW, Console::BOLD);
        $this->stdout("  iiko-server: {$this->baseUrl}\n");
        $this->stdout("  iiko-login: {$login}\n");
        $this->stdout("  iiko-password: " . (empty($password) ? "(пусто)" : str_repeat("*", strlen($password))) . "\n");
        $this->stdout("\n");

        if (empty($this->baseUrl) || empty($login) || empty($password)) {
            $this->stderr("ОШИБКА: Не заполнены настройки iiko в таблице settings!\n", Console::FG_RED, Console::BOLD);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Авторизация
        $this->stdout("1. Авторизация...\n", Console::FG_YELLOW);
        $hash = sha1($password);
        $authUrl = "{$this->baseUrl}auth?login={$login}&pass={$hash}";
        $this->stdout("   URL: {$this->baseUrl}auth?login={$login}&pass=<hash>\n");

        $token = $this->makeRequest($authUrl);

        if (!$token) {
            $this->stderr("   ОШИБКА: Пустой ответ при авторизации\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("   Ответ (первые 200 символов): ", Console::FG_PURPLE);
        $this->stdout(substr($token, 0, 200) . "\n", Console::BOLD);

        if (strlen($token) > 100 || strpos($token, 'error') !== false || strpos($token, 'Error') !== false) {
            $this->stderr("   ВНИМАНИЕ: Похоже на ошибку авторизации!\n", Console::FG_RED);
            $this->stderr("   Полный ответ:\n{$token}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->token = $token;
        $this->stdout("   Токен получен (длина: " . strlen($token) . ")\n", Console::FG_GREEN);
        $this->stdout("\n");

        // Запрос данных
        $endpoints = [
            'products' => 'products',
            'departments' => 'corporation/departments',
            'suppliers' => 'suppliers',
            'stores' => 'corporation/stores',
            'groups' => 'corporation/groups',
        ];

        if (!isset($endpoints[$endpoint])) {
            $this->stderr("Неизвестный endpoint: {$endpoint}\n", Console::FG_RED);
            $this->stdout("Доступные: " . implode(", ", array_keys($endpoints)) . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $url = "{$this->baseUrl}{$endpoints[$endpoint]}?key={$this->token}";
        $this->stdout("2. Запрос данных ({$endpoint})...\n", Console::FG_YELLOW);
        $this->stdout("   URL: {$this->baseUrl}{$endpoints[$endpoint]}?key=<token>\n", Console::FG_PURPLE);

        $rawData = $this->makeRequest($url);

        if (!$rawData) {
            $this->stderr("   ОШИБКА: Пустой ответ\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("\n");
        $this->stdout("3. Сырой ответ от iiko:\n", Console::FG_YELLOW);
        $this->stdout("   Размер ответа: " . strlen($rawData) . " байт\n", Console::FG_PURPLE);
        $this->stdout("   Тип: " . (strpos($rawData, '<?xml') !== false ? 'XML' : 'Другой') . "\n", Console::FG_PURPLE);
        $this->stdout("\n");

        // Показываем первые 2000 символов сырых данных
        $this->stdout("   --- НАЧАЛО ОТВЕТА (первые 2000 символов) ---\n", Console::FG_CYAN);
        $this->stdout(substr($rawData, 0, 2000) . "\n");
        if (strlen($rawData) > 2000) {
            $this->stdout("   ... (обрезано, всего " . strlen($rawData) . " байт)\n", Console::FG_PURPLE);
        }
        $this->stdout("   --- КОНЕЦ ОТВЕТА ---\n", Console::FG_CYAN);

        // Парсим XML
        $this->stdout("\n");
        $this->stdout("4. Парсинг XML...\n", Console::FG_YELLOW);

        $data = $this->xmlToArray($rawData);

        if (empty($data)) {
            $this->stderr("   ОШИБКА: Не удалось распарсить XML\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("   Ключи в ответе: " . implode(", ", array_keys($data)) . "\n", Console::FG_GREEN);

        // Подсчитываем количество элементов
        $keyMap = [
            'products' => 'productDto',
            'departments' => 'corporateItemDto',
            'suppliers' => 'employee',
            'stores' => 'corporateItemDto',
            'groups' => 'groupDto',
        ];

        $expectedKey = $keyMap[$endpoint];
        if (isset($data[$expectedKey])) {
            $count = is_array($data[$expectedKey]) ? count($data[$expectedKey]) : 0;
            $this->stdout("   Количество элементов в '{$expectedKey}': {$count}\n", Console::FG_GREEN);

            if ($count > 0 && $this->verbose) {
                $this->stdout("\n   Пример первого элемента:\n", Console::FG_YELLOW);
                $firstItem = is_array($data[$expectedKey][0] ?? null) ? $data[$expectedKey][0] : $data[$expectedKey];
                $this->stdout("   " . Json::encode($firstItem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            }
        } else {
            $this->stderr("   ВНИМАНИЕ: Ключ '{$expectedKey}' не найден в ответе!\n", Console::FG_RED);
            $this->stdout("   Доступные ключи: " . implode(", ", array_keys($data)) . "\n", Console::FG_YELLOW);

            // Показываем структуру данных
            $this->stdout("\n   Структура ответа:\n", Console::FG_YELLOW);
            $this->stdout("   " . Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
        }

        $this->stdout("\n");
        return ExitCode::OK;
    }

    /**
     * HTTP запрос через cURL (более надёжный)
     * @param string $url
     * @param bool $verbose показывать прогресс
     * @return string|false
     */
    private function makeRequest($url, $verbose = false)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 0,            // без лимита
            CURLOPT_CONNECTTIMEOUT => 60,    // 60 сек на подключение
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_LOW_SPEED_LIMIT => 100,  // минимум 100 байт/сек
            CURLOPT_LOW_SPEED_TIME => 60,    // в течение 60 сек
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Expect:',  // отключаем Expect: 100-continue
            ],
        ]);

        if ($verbose) {
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) {
                if ($downloadSize > 0) {
                    $percent = round($downloaded / $downloadSize * 100);
                    echo "\r   Загрузка: {$downloaded} / {$downloadSize} байт ({$percent}%)   ";
                } else {
                    echo "\r   Загружено: {$downloaded} байт...   ";
                }
                return 0;
            });
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        if ($verbose) {
            echo "\n";
        }

        if ($errno) {
            $this->stderr("   cURL ошибка [{$errno}]: {$error}\n", Console::FG_RED);
            $this->stderr("   Время: {$totalTime} сек, получено: " . strlen($result) . " байт\n", Console::FG_RED);
            return false;
        }

        if ($httpCode !== 200) {
            $this->stderr("   HTTP код: {$httpCode}\n", Console::FG_RED);
        }

        if ($verbose) {
            $this->stdout("   Время загрузки: " . round($totalTime, 2) . " сек\n", Console::FG_GREEN);
        }

        return $result;
    }

    /**
     * Конвертация XML в массив
     * @param string $xml
     * @return array|null
     */
    private function xmlToArray($xml)
    {
        libxml_use_internal_errors(true);
        $data = simplexml_load_string($xml);

        if ($data === false) {
            $this->stderr("   Ошибки XML парсинга:\n", Console::FG_RED);
            foreach (libxml_get_errors() as $error) {
                $this->stderr("   - Строка {$error->line}: {$error->message}", Console::FG_RED);
            }
            libxml_clear_errors();
            return null;
        }

        $json = Json::encode($data);
        return Json::decode($json, true);
    }

    /**
     * Тест парсинга с выводом структуры
     *
     * php yii sync-iiko/debug
     *
     * @return int
     */
    public function actionDebug()
    {
        $this->stdout("\n=== ДЕБАГ ПАРСИНГА ===\n\n", Console::FG_CYAN);

        // Получаем настройки и авторизуемся
        $this->baseUrl = Settings::getValue("iiko-server");
        $login = Settings::getValue("iiko-login");
        $password = Settings::getValue("iiko-password");

        $hash = sha1($password);
        $authUrl = "{$this->baseUrl}auth?login={$login}&pass={$hash}";
        $this->token = $this->makeRequest($authUrl);

        if (empty($this->token) || strlen($this->token) > 50) {
            $this->stderr("Ошибка авторизации\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Токен: OK\n", Console::FG_GREEN);

        // Запрос products
        $url = "{$this->baseUrl}products?key={$this->token}";
        $this->stdout("Загрузка products...\n", Console::FG_YELLOW);
        $rawXml = $this->makeRequest($url, true);

        $this->stdout("Размер ответа: " . strlen($rawXml) . " байт\n");

        // Парсим XML напрямую
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawXml);

        if ($xml === false) {
            $this->stderr("XML не распарсился:\n", Console::FG_RED);
            foreach (libxml_get_errors() as $error) {
                $this->stderr("  - {$error->message}", Console::FG_RED);
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("XML распарсился успешно!\n", Console::FG_GREEN);
        $this->stdout("Корневой элемент: " . $xml->getName() . "\n", Console::FG_YELLOW);
        $this->stdout("Количество дочерних: " . count($xml->children()) . "\n", Console::FG_YELLOW);

        // Показываем имена дочерних элементов
        $childNames = [];
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if (!isset($childNames[$name])) {
                $childNames[$name] = 0;
            }
            $childNames[$name]++;
        }

        $this->stdout("Дочерние элементы:\n", Console::FG_YELLOW);
        foreach ($childNames as $name => $count) {
            $this->stdout("  - {$name}: {$count} шт.\n");
        }

        // Конвертируем в JSON/array
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $this->stdout("\nСтруктура после конвертации в массив:\n", Console::FG_YELLOW);
        $this->stdout("Ключи: " . implode(", ", array_keys($array)) . "\n");

        // Проверяем ключ productDto
        if (isset($array['productDto'])) {
            $count = count($array['productDto']);
            $this->stdout("productDto найден, элементов: {$count}\n", Console::FG_GREEN);

            // Фильтруем по типу GOODS/PREPARED
            $goods = 0;
            $prepared = 0;
            $other = 0;
            foreach ($array['productDto'] as $item) {
                $type = $item['productType'] ?? '';
                if ($type === 'GOODS') $goods++;
                elseif ($type === 'PREPARED') $prepared++;
                else $other++;
            }
            $this->stdout("  - GOODS: {$goods}\n");
            $this->stdout("  - PREPARED: {$prepared}\n");
            $this->stdout("  - Другие (пропускаются): {$other}\n");
        } else {
            $this->stderr("productDto НЕ найден!\n", Console::FG_RED);
        }

        $this->stdout("\n");
        return ExitCode::OK;
    }
}
