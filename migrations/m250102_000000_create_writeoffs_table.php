<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_writeoffs}}`.
 */
class m250102_000000_create_writeoffs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%product_writeoffs}}', [
            'id' => $this->primaryKey(),
            'store_id' => $this->integer()->notNull()->comment('ID магазина'),
            'product_id' => $this->integer()->notNull()->comment('ID продукта'),
            'count' => $this->decimal(10, 2)->notNull()->comment('Количество списания'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'),
            'approved_count' => $this->decimal(10, 2)->comment('Утвержденное количество'),
            'status' => $this->string(20)->notNull()->defaultValue('new')->comment('Статус (new, approved)'),
            'approved_by' => $this->integer()->comment('ID пользователя, утвердившего списание'),
            'approved_at' => $this->timestamp()->comment('Дата утверждения'),
        ]);

        // Добавляем индексы
        $this->createIndex('idx-product_writeoffs-store_id', '{{%product_writeoffs}}', 'store_id');
        $this->createIndex('idx-product_writeoffs-product_id', '{{%product_writeoffs}}', 'product_id');
        $this->createIndex('idx-product_writeoffs-status', '{{%product_writeoffs}}', 'status');
        $this->createIndex('idx-product_writeoffs-created_at', '{{%product_writeoffs}}', 'created_at');

        // Добавляем внешние ключи
        $this->addForeignKey(
            'fk-product_writeoffs-store_id',
            '{{%product_writeoffs}}',
            'store_id',
            '{{%stores}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-product_writeoffs-product_id',
            '{{%product_writeoffs}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-product_writeoffs-approved_by',
            '{{%product_writeoffs}}',
            'approved_by',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешние ключи
        $this->dropForeignKey('fk-product_writeoffs-store_id', '{{%product_writeoffs}}');
        $this->dropForeignKey('fk-product_writeoffs-product_id', '{{%product_writeoffs}}');
        $this->dropForeignKey('fk-product_writeoffs-approved_by', '{{%product_writeoffs}}');

        // Удаляем индексы
        $this->dropIndex('idx-product_writeoffs-store_id', '{{%product_writeoffs}}');
        $this->dropIndex('idx-product_writeoffs-product_id', '{{%product_writeoffs}}');
        $this->dropIndex('idx-product_writeoffs-status', '{{%product_writeoffs}}');
        $this->dropIndex('idx-product_writeoffs-created_at', '{{%product_writeoffs}}');

        // Удаляем таблицу
        $this->dropTable('{{%product_writeoffs}}');
    }
}
