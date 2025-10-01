<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%oil_inventory_history}}`.
 */
class m250101_000000_create_oil_inventory_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%oil_inventory_history}}', [
            'id' => $this->primaryKey(),
            'oil_inventory_id' => $this->integer()->notNull()->comment('ID записи учета масла'),
            'user_id' => $this->integer()->notNull()->comment('ID пользователя, внесшего изменение'),
            'field_name' => $this->string(50)->notNull()->comment('Название измененного поля'),
            'old_value' => $this->string(255)->comment('Старое значение'),
            'new_value' => $this->string(255)->comment('Новое значение'),
            'action' => $this->string(20)->notNull()->comment('Действие (create, update, delete)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата и время изменения'),
        ]);

        // Добавляем индексы
        $this->createIndex('idx-oil_inventory_history-oil_inventory_id', '{{%oil_inventory_history}}', 'oil_inventory_id');
        $this->createIndex('idx-oil_inventory_history-user_id', '{{%oil_inventory_history}}', 'user_id');
        $this->createIndex('idx-oil_inventory_history-created_at', '{{%oil_inventory_history}}', 'created_at');

        // Добавляем внешние ключи
        $this->addForeignKey(
            'fk-oil_inventory_history-oil_inventory_id',
            '{{%oil_inventory_history}}',
            'oil_inventory_id',
            '{{%oil_inventory}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-oil_inventory_history-user_id',
            '{{%oil_inventory_history}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешние ключи
        $this->dropForeignKey('fk-oil_inventory_history-oil_inventory_id', '{{%oil_inventory_history}}');
        $this->dropForeignKey('fk-oil_inventory_history-user_id', '{{%oil_inventory_history}}');

        // Удаляем индексы
        $this->dropIndex('idx-oil_inventory_history-oil_inventory_id', '{{%oil_inventory_history}}');
        $this->dropIndex('idx-oil_inventory_history-user_id', '{{%oil_inventory_history}}');
        $this->dropIndex('idx-oil_inventory_history-created_at', '{{%oil_inventory_history}}');

        // Удаляем таблицу
        $this->dropTable('{{%oil_inventory_history}}');
    }
}
