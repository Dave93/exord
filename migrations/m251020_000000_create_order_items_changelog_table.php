<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order_items_changelog}}`.
 */
class m251020_000000_create_order_items_changelog_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order_items_changelog}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull()->comment('ID заказа'),
            'productId' => $this->string(36)->notNull()->comment('ID продукта'),
            'action' => $this->string(20)->notNull()->comment('Действие: added, deleted, updated, restored'),
            'old_quantity' => $this->decimal(10, 3)->null()->comment('Старое количество'),
            'new_quantity' => $this->decimal(10, 3)->null()->comment('Новое количество'),
            'userId' => $this->integer()->notNull()->comment('ID пользователя, который внёс изменение'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата и время изменения'),
        ]);

        // Создаем индексы для оптимизации запросов
        $this->createIndex('idx-order_items_changelog-orderId', '{{%order_items_changelog}}', 'orderId');
        $this->createIndex('idx-order_items_changelog-productId', '{{%order_items_changelog}}', 'productId');
        $this->createIndex('idx-order_items_changelog-userId', '{{%order_items_changelog}}', 'userId');
        $this->createIndex('idx-order_items_changelog-created_at', '{{%order_items_changelog}}', 'created_at');

        // Добавляем внешние ключи
        $this->addForeignKey(
            'fk-order_items_changelog-orderId',
            '{{%order_items_changelog}}',
            'orderId',
            '{{%orders}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-order_items_changelog-productId',
            '{{%order_items_changelog}}',
            'productId',
            '{{%products}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-order_items_changelog-userId',
            '{{%order_items_changelog}}',
            'userId',
            '{{%user}}',
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
        $this->dropForeignKey('fk-order_items_changelog-userId', '{{%order_items_changelog}}');
        $this->dropForeignKey('fk-order_items_changelog-productId', '{{%order_items_changelog}}');
        $this->dropForeignKey('fk-order_items_changelog-orderId', '{{%order_items_changelog}}');

        // Удаляем индексы
        $this->dropIndex('idx-order_items_changelog-created_at', '{{%order_items_changelog}}');
        $this->dropIndex('idx-order_items_changelog-userId', '{{%order_items_changelog}}');
        $this->dropIndex('idx-order_items_changelog-productId', '{{%order_items_changelog}}');
        $this->dropIndex('idx-order_items_changelog-orderId', '{{%order_items_changelog}}');

        // Удаляем таблицу
        $this->dropTable('{{%order_items_changelog}}');
    }
}
