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
        // Создаем главную таблицу списаний
        $this->createTable('{{%product_writeoffs}}', [
            'id' => $this->primaryKey(),
            'store_id' => $this->integer()->notNull()->comment('ID магазина'),
            'created_by' => $this->integer()->notNull()->comment('ID пользователя, создавшего списание'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'),
            'status' => $this->string(20)->notNull()->defaultValue('new')->comment('Статус (new, approved)'),
            'approved_by' => $this->integer()->comment('ID пользователя, утвердившего списание'),
            'approved_at' => $this->timestamp()->comment('Дата утверждения'),
            'comment' => $this->text()->comment('Комментарий'),
        ]);

        // Создаем таблицу позиций списания
        $this->createTable('{{%product_writeoff_items}}', [
            'id' => $this->primaryKey(),
            'writeoff_id' => $this->integer()->notNull()->comment('ID списания'),
            'product_id' => $this->string(36)->notNull()->comment('ID продукта (UUID)'),
            'count' => $this->decimal(10, 2)->notNull()->comment('Количество списания'),
            'approved_count' => $this->decimal(10, 2)->comment('Утвержденное количество'),
        ]);

        // Индексы для главной таблицы
        $this->createIndex('idx-product_writeoffs-store_id', '{{%product_writeoffs}}', 'store_id');
        $this->createIndex('idx-product_writeoffs-status', '{{%product_writeoffs}}', 'status');
        $this->createIndex('idx-product_writeoffs-created_at', '{{%product_writeoffs}}', 'created_at');
        $this->createIndex('idx-product_writeoffs-created_by', '{{%product_writeoffs}}', 'created_by');

        // Индексы для таблицы позиций
        $this->createIndex('idx-product_writeoff_items-writeoff_id', '{{%product_writeoff_items}}', 'writeoff_id');
        $this->createIndex('idx-product_writeoff_items-product_id', '{{%product_writeoff_items}}', 'product_id');

        // Внешние ключи для главной таблицы
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
            'fk-product_writeoffs-created_by',
            '{{%product_writeoffs}}',
            'created_by',
            '{{%users}}',
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

        // Внешние ключи для таблицы позиций
        $this->addForeignKey(
            'fk-product_writeoff_items-writeoff_id',
            '{{%product_writeoff_items}}',
            'writeoff_id',
            '{{%product_writeoffs}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-product_writeoff_items-product_id',
            '{{%product_writeoff_items}}',
            'product_id',
            '{{%products}}',
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
        // Удаляем внешние ключи таблицы позиций
        $this->dropForeignKey('fk-product_writeoff_items-writeoff_id', '{{%product_writeoff_items}}');
        $this->dropForeignKey('fk-product_writeoff_items-product_id', '{{%product_writeoff_items}}');

        // Удаляем индексы таблицы позиций
        $this->dropIndex('idx-product_writeoff_items-writeoff_id', '{{%product_writeoff_items}}');
        $this->dropIndex('idx-product_writeoff_items-product_id', '{{%product_writeoff_items}}');

        // Удаляем таблицу позиций
        $this->dropTable('{{%product_writeoff_items}}');

        // Удаляем внешние ключи главной таблицы
        $this->dropForeignKey('fk-product_writeoffs-store_id', '{{%product_writeoffs}}');
        $this->dropForeignKey('fk-product_writeoffs-created_by', '{{%product_writeoffs}}');
        $this->dropForeignKey('fk-product_writeoffs-approved_by', '{{%product_writeoffs}}');

        // Удаляем индексы главной таблицы
        $this->dropIndex('idx-product_writeoffs-store_id', '{{%product_writeoffs}}');
        $this->dropIndex('idx-product_writeoffs-status', '{{%product_writeoffs}}');
        $this->dropIndex('idx-product_writeoffs-created_at', '{{%product_writeoffs}}');
        $this->dropIndex('idx-product_writeoffs-created_by', '{{%product_writeoffs}}');

        // Удаляем главную таблицу
        $this->dropTable('{{%product_writeoffs}}');
    }
}
