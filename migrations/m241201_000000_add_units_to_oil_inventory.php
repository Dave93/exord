<?php

use yii\db\Migration;

/**
 * Добавляет поддержку единиц измерения для учёта масел
 */
class m241201_000000_add_units_to_oil_inventory extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Добавляем поле для возврата в килограммах
        $this->addColumn('{{%oil_inventory}}', 'return_amount_kg', $this->decimal(10, 3)->defaultValue(0)->comment('Возврат в килограммах'));
        
        // Конвертируем существующие данные из литров в килограммы (обратная конвертация)
        $this->execute('UPDATE {{%oil_inventory}} SET return_amount_kg = return_amount / 1.1 WHERE return_amount > 0');
        
        // Добавляем индекс для поля возврата в кг
        $this->createIndex('idx-oil_inventory-return_amount_kg', '{{%oil_inventory}}', 'return_amount_kg');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем индекс
        $this->dropIndex('idx-oil_inventory-return_amount_kg', '{{%oil_inventory}}');
        
        // Удаляем добавленную колонку
        $this->dropColumn('{{%oil_inventory}}', 'return_amount_kg');
    }
} 