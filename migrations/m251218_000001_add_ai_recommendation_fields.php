<?php

use yii\db\Migration;

/**
 * Добавляет поля для отслеживания ИИ-рекомендаций
 *
 * В orders:
 * - ai_input_tokens: количество входных токенов
 * - ai_output_tokens: количество выходных токенов
 * - ai_cost: стоимость запроса в долларах
 * - ai_recommended_at: время получения рекомендации
 *
 * В order_items:
 * - ai_recommended_quantity: рекомендованное ИИ количество
 */
class m251218_000001_add_ai_recommendation_fields extends Migration
{
    public function safeUp()
    {
        // Добавляем поля в orders
        $this->addColumn('orders', 'ai_input_tokens', $this->integer()->null()->comment('Входные токены ИИ'));
        $this->addColumn('orders', 'ai_output_tokens', $this->integer()->null()->comment('Выходные токены ИИ'));
        $this->addColumn('orders', 'ai_cost', $this->decimal(10, 6)->null()->comment('Стоимость ИИ-запроса в USD'));
        $this->addColumn('orders', 'ai_recommended_at', $this->dateTime()->null()->comment('Время ИИ-рекомендации'));

        // Добавляем поле в order_items
        $this->addColumn('order_items', 'ai_recommended_quantity', $this->decimal(10, 3)->null()->comment('Рекомендованное ИИ количество'));
    }

    public function safeDown()
    {
        $this->dropColumn('orders', 'ai_input_tokens');
        $this->dropColumn('orders', 'ai_output_tokens');
        $this->dropColumn('orders', 'ai_cost');
        $this->dropColumn('orders', 'ai_recommended_at');

        $this->dropColumn('order_items', 'ai_recommended_quantity');
    }
}
