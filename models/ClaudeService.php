<?php

namespace app\models;

use Yii;
use yii\base\Exception;

/**
 * Сервис для работы с Claude API (Anthropic)
 * Используется для ИИ-функций, таких как автоформирование заказов
 */
class ClaudeService
{
    private $apiKey;
    private $baseURL = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-haiku-20240307'; // Оптимальный по цене/качеству

    // Цены за 1 миллион токенов (Claude Haiku 3.5)
    const PRICE_INPUT_PER_MILLION = 1.00;  // $1.00 за 1M input tokens
    const PRICE_OUTPUT_PER_MILLION = 5.00; // $5.00 за 1M output tokens

    // Последние данные об использовании
    private $lastUsage = null;

    public function __construct()
    {
        $this->apiKey = Settings::getValue("claude-api-key");
        if (empty($this->apiKey)) {
            throw new Exception('Claude API key not configured. Add "claude-api-key" to settings.');
        }
    }

    /**
     * Отправить сообщение в Claude API
     *
     * @param string $systemPrompt Системный промпт
     * @param string $userMessage Сообщение пользователя
     * @param int $maxTokens Максимальное количество токенов в ответе
     * @return array|null Ответ от API
     */
    public function sendMessage($systemPrompt, $userMessage, $maxTokens = 1024)
    {
        $data = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Yii::error("Claude API curl error: " . $error, 'claude');
            return null;
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            Yii::error("Claude API error (HTTP $httpCode): " . $response, 'claude');
            return null;
        }

        // Сохраняем данные об использовании токенов
        if (isset($result['usage'])) {
            $this->lastUsage = $result['usage'];
        }

        return $result;
    }

    /**
     * Извлечь текст ответа из результата API
     *
     * @param array $response Ответ от API
     * @return string|null Текст ответа
     */
    public function extractText($response)
    {
        if (isset($response['content'][0]['text'])) {
            return $response['content'][0]['text'];
        }
        return null;
    }

    /**
     * Проверить доступность API
     *
     * @return bool
     */
    public function isAvailable()
    {
        return !empty($this->apiKey);
    }

    /**
     * Получить данные об использовании токенов из последнего запроса
     *
     * @return array|null ['input_tokens' => int, 'output_tokens' => int]
     */
    public function getLastUsage()
    {
        return $this->lastUsage;
    }

    /**
     * Получить количество входных токенов из последнего запроса
     *
     * @return int
     */
    public function getInputTokens()
    {
        return $this->lastUsage['input_tokens'] ?? 0;
    }

    /**
     * Получить количество выходных токенов из последнего запроса
     *
     * @return int
     */
    public function getOutputTokens()
    {
        return $this->lastUsage['output_tokens'] ?? 0;
    }

    /**
     * Рассчитать стоимость последнего запроса в USD
     *
     * @return float
     */
    public function calculateCost()
    {
        $inputTokens = $this->getInputTokens();
        $outputTokens = $this->getOutputTokens();

        $inputCost = ($inputTokens / 1000000) * self::PRICE_INPUT_PER_MILLION;
        $outputCost = ($outputTokens / 1000000) * self::PRICE_OUTPUT_PER_MILLION;

        return $inputCost + $outputCost;
    }

    /**
     * Получить полную статистику использования
     *
     * @return array
     */
    public function getUsageStats()
    {
        return [
            'input_tokens' => $this->getInputTokens(),
            'output_tokens' => $this->getOutputTokens(),
            'total_tokens' => $this->getInputTokens() + $this->getOutputTokens(),
            'cost_usd' => $this->calculateCost(),
            'model' => $this->model
        ];
    }
}
