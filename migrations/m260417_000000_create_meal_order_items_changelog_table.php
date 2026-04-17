<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%meal_order_items_changelog}}`.
 */
class m260417_000000_create_meal_order_items_changelog_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%meal_order_items_changelog}}', [
            'id' => $this->primaryKey(),
            'mealOrderId' => $this->integer()->notNull()->comment('ID заказа блюд'),
            'dishId' => $this->integer()->notNull()->comment('ID блюда'),
            'action' => $this->string(20)->notNull()->comment('Действие: added, deleted, updated, restored'),
            'old_quantity' => $this->decimal(10, 3)->null()->comment('Старое количество'),
            'new_quantity' => $this->decimal(10, 3)->null()->comment('Новое количество'),
            'userId' => $this->integer()->notNull()->comment('ID пользователя, который внёс изменение'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата и время изменения'),
        ]);

        $this->createIndex('idx-meal_order_items_changelog-mealOrderId', '{{%meal_order_items_changelog}}', 'mealOrderId');
        $this->createIndex('idx-meal_order_items_changelog-dishId', '{{%meal_order_items_changelog}}', 'dishId');
        $this->createIndex('idx-meal_order_items_changelog-userId', '{{%meal_order_items_changelog}}', 'userId');
        $this->createIndex('idx-meal_order_items_changelog-created_at', '{{%meal_order_items_changelog}}', 'created_at');

        $this->addForeignKey(
            'fk-meal_order_items_changelog-mealOrderId',
            '{{%meal_order_items_changelog}}',
            'mealOrderId',
            '{{%meal_orders}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-meal_order_items_changelog-dishId',
            '{{%meal_order_items_changelog}}',
            'dishId',
            '{{%dishes}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-meal_order_items_changelog-userId',
            '{{%meal_order_items_changelog}}',
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
        $this->dropForeignKey('fk-meal_order_items_changelog-userId', '{{%meal_order_items_changelog}}');
        $this->dropForeignKey('fk-meal_order_items_changelog-dishId', '{{%meal_order_items_changelog}}');
        $this->dropForeignKey('fk-meal_order_items_changelog-mealOrderId', '{{%meal_order_items_changelog}}');

        $this->dropIndex('idx-meal_order_items_changelog-created_at', '{{%meal_order_items_changelog}}');
        $this->dropIndex('idx-meal_order_items_changelog-userId', '{{%meal_order_items_changelog}}');
        $this->dropIndex('idx-meal_order_items_changelog-dishId', '{{%meal_order_items_changelog}}');
        $this->dropIndex('idx-meal_order_items_changelog-mealOrderId', '{{%meal_order_items_changelog}}');

        $this->dropTable('{{%meal_order_items_changelog}}');
    }
}
