<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%oil_inventory}}`.
 */
class m240101_000000_create_oil_inventory_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%oil_inventory}}', [
            'id' => $this->primaryKey(),
            'store_id' => $this->string(36)->notNull()->comment('ID магазина'),
            'opening_balance' => $this->decimal(10, 3)->defaultValue(0)->comment('Остаток на начало дня'),
            'income' => $this->decimal(10, 3)->defaultValue(0)->comment('Приход'),
            'return_amount' => $this->decimal(10, 3)->defaultValue(0)->comment('Возврат'),
            'apparatus' => $this->decimal(10, 3)->defaultValue(0)->comment('Аппарат'),
            'new_oil' => $this->decimal(10, 3)->defaultValue(0)->comment('Новое масло'),
            'evaporation' => $this->decimal(10, 3)->defaultValue(0)->comment('Испарение'),
            'closing_balance' => $this->decimal(10, 3)->defaultValue(0)->comment('Остаток на конец дня'),
            'status' => $this->string(20)->defaultValue('новый')->comment('Статус'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Дата обновления'),
        ]);

        // Добавляем индексы
        $this->createIndex('idx-oil_inventory-store_id', '{{%oil_inventory}}', 'store_id');
        $this->createIndex('idx-oil_inventory-status', '{{%oil_inventory}}', 'status');
        $this->createIndex('idx-oil_inventory-created_at', '{{%oil_inventory}}', 'created_at');

        // Добавляем внешний ключ к таблице stores (если она существует)
        try {
            $this->addForeignKey(
                'fk-oil_inventory-store_id',
                '{{%oil_inventory}}',
                'store_id',
                '{{%stores}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        } catch (Exception $e) {
            // Если таблица stores не существует, продолжаем без внешнего ключа
            echo "Warning: Could not create foreign key to stores table. Table may not exist.\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешний ключ, если он существует
        try {
            $this->dropForeignKey('fk-oil_inventory-store_id', '{{%oil_inventory}}');
        } catch (Exception $e) {
            // Игнорируем ошибку, если внешний ключ не существует
        }

        // Удаляем индексы
        $this->dropIndex('idx-oil_inventory-store_id', '{{%oil_inventory}}');
        $this->dropIndex('idx-oil_inventory-status', '{{%oil_inventory}}');
        $this->dropIndex('idx-oil_inventory-created_at', '{{%oil_inventory}}');

        // Удаляем таблицу
        $this->dropTable('{{%oil_inventory}}');
    }
} 