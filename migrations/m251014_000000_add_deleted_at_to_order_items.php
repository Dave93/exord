<?php

use yii\db\Migration;

/**
 * Handles adding deleted_at to table `{{%order_items}}`.
 */
class m251014_000000_add_deleted_at_to_order_items extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order_items}}', 'deleted_at', $this->timestamp()->null()->comment('Дата удаления (soft delete)'));
        $this->addColumn('{{%order_items}}', 'deleted_by', $this->integer()->null()->comment('ID пользователя, который удалил запись'));

        // Создаем индексы для оптимизации запросов
        $this->createIndex('idx-order_items-deleted_at', '{{%order_items}}', 'deleted_at');
        $this->createIndex('idx-order_items-deleted_by', '{{%order_items}}', 'deleted_by');

        // Добавляем внешний ключ на таблицу users
        $this->addForeignKey(
            'fk-order_items-deleted_by',
            '{{%order_items}}',
            'deleted_by',
            '{{%user}}',
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
        // Удаляем внешний ключ
        $this->dropForeignKey('fk-order_items-deleted_by', '{{%order_items}}');

        // Удаляем индексы
        $this->dropIndex('idx-order_items-deleted_by', '{{%order_items}}');
        $this->dropIndex('idx-order_items-deleted_at', '{{%order_items}}');

        // Удаляем колонки
        $this->dropColumn('{{%order_items}}', 'deleted_by');
        $this->dropColumn('{{%order_items}}', 'deleted_at');
    }
}
