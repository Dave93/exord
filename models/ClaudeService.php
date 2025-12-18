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

        Yii::info("ClaudeService: Preparing request to model: " . $this->model, 'claude');
        Yii::info("ClaudeService: System prompt length: " . strlen($systemPrompt), 'claude');
        Yii::info("ClaudeService: User message length: " . strlen($userMessage), 'claude');
        Yii::info("ClaudeService: Max tokens: " . $maxTokens, 'claude');

        $jsonData = json_encode($data);
        if ($jsonData === false) {
            Yii::error("ClaudeService: Failed to encode request data: " . json_last_error_msg(), 'claude');
            return null;
        }
        Yii::info("ClaudeService: Request JSON length: " . strlen($jsonData), 'claude');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        Yii::info("ClaudeService: Sending request to " . $this->baseURL, 'claude');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        Yii::info("ClaudeService: HTTP Code: " . $httpCode, 'claude');
        Yii::info("ClaudeService: Response length: " . strlen($response), 'claude');
        Yii::info("ClaudeService: Raw response (first 2000 chars): " . substr($response, 0, 2000), 'claude');

        if ($error) {
            Yii::error("ClaudeService: curl error ($errno): " . $error, 'claude');
            return null;
        }

        $result = json_decode($response, true);
        if ($result === null) {
            Yii::error("ClaudeService: Failed to decode JSON response: " . json_last_error_msg(), 'claude');
            Yii::error("ClaudeService: Raw response for debug: " . $response, 'claude');
            return null;
        }

        Yii::info("ClaudeService: Decoded response keys: " . implode(', ', array_keys($result)), 'claude');

        if ($httpCode !== 200) {
            Yii::error("ClaudeService: API error (HTTP $httpCode): " . $response, 'claude');
            if (isset($result['error'])) {
                Yii::error("ClaudeService: Error type: " . ($result['error']['type'] ?? 'unknown'), 'claude');
                Yii::error("ClaudeService: Error message: " . ($result['error']['message'] ?? 'unknown'), 'claude');
            }
            return null;
        }

        // Log response structure
        if (isset($result['content'])) {
            Yii::info("ClaudeService: Content blocks count: " . count($result['content']), 'claude');
            foreach ($result['content'] as $i => $block) {
                Yii::info("ClaudeService: Block $i type: " . ($block['type'] ?? 'unknown'), 'claude');
                if (isset($block['text'])) {
                    Yii::info("ClaudeService: Block $i text length: " . strlen($block['text']), 'claude');
                }
            }
        } else {
            Yii::warning("ClaudeService: No 'content' key in response", 'claude');
        }

        // Save usage data
        if (isset($result['usage'])) {
            $this->lastUsage = $result['usage'];
            Yii::info("ClaudeService: Usage - input: " . $result['usage']['input_tokens'] . ", output: " . $result['usage']['output_tokens'], 'claude');
        } else {
            Yii::warning("ClaudeService: No 'usage' key in response", 'claude');
        }

        Yii::info("ClaudeService: Request completed successfully", 'claude');

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
        Yii::info("ClaudeService::extractText: Starting extraction", 'claude');

        if ($response === null) {
            Yii::warning("ClaudeService::extractText: Response is null", 'claude');
            return null;
        }

        if (!is_array($response)) {
            Yii::warning("ClaudeService::extractText: Response is not an array, type: " . gettype($response), 'claude');
            return null;
        }

        Yii::info("ClaudeService::extractText: Response keys: " . implode(', ', array_keys($response)), 'claude');

        if (!isset($response['content'])) {
            Yii::warning("ClaudeService::extractText: No 'content' key in response", 'claude');
            return null;
        }

        if (!is_array($response['content']) || empty($response['content'])) {
            Yii::warning("ClaudeService::extractText: 'content' is empty or not array", 'claude');
            return null;
        }

        Yii::info("ClaudeService::extractText: Content has " . count($response['content']) . " blocks", 'claude');

        if (!isset($response['content'][0]['text'])) {
            Yii::warning("ClaudeService::extractText: No 'text' in first content block", 'claude');
            Yii::info("ClaudeService::extractText: First block keys: " . implode(', ', array_keys($response['content'][0])), 'claude');
            return null;
        }

        $text = $response['content'][0]['text'];
        Yii::info("ClaudeService::extractText: Extracted text length: " . strlen($text), 'claude');
        Yii::info("ClaudeService::extractText: First 500 chars: " . substr($text, 0, 500), 'claude');

        return $text;
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
