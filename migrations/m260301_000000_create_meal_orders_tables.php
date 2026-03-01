<?php

use yii\db\Migration;

class m260301_000000_create_meal_orders_tables extends Migration
{
    public function safeUp()
    {
        // Таблица блюд
        $this->createTable('dishes', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'unit' => $this->string(20)->defaultValue('шт'),
            'active' => $this->tinyInteger(1)->defaultValue(1),
        ]);

        // Таблица заказов блюд
        $this->createTable('meal_orders', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'storeId' => $this->string(36),
            'date' => $this->dateTime(),
            'addDate' => $this->dateTime(),
            'comment' => $this->text(),
            'state' => $this->integer()->defaultValue(0),
            'editable' => $this->integer()->defaultValue(1),
            'is_locked' => $this->tinyInteger(1)->defaultValue(0),
            'deleted_at' => $this->dateTime(),
            'deleted_by' => $this->integer(),
        ]);

        $this->createIndex('idx-meal_orders-userId', 'meal_orders', 'userId');
        $this->createIndex('idx-meal_orders-storeId', 'meal_orders', 'storeId');
        $this->createIndex('idx-meal_orders-state', 'meal_orders', 'state');
        $this->createIndex('idx-meal_orders-date', 'meal_orders', 'date');
        $this->createIndex('idx-meal_orders-deleted_at', 'meal_orders', 'deleted_at');

        $this->addForeignKey('fk-meal_orders-userId', 'meal_orders', 'userId', 'user', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-meal_orders-deleted_by', 'meal_orders', 'deleted_by', 'user', 'id', 'SET NULL', 'CASCADE');

        // Таблица позиций заказов блюд
        $this->createTable('meal_order_items', [
            'id' => $this->primaryKey(),
            'mealOrderId' => $this->integer()->notNull(),
            'dishId' => $this->integer()->notNull(),
            'quantity' => $this->decimal(10, 3),
            'userId' => $this->integer(),
            'deleted_at' => $this->timestamp()->null(),
            'deleted_by' => $this->integer(),
        ]);

        $this->createIndex('idx-meal_order_items-mealOrderId', 'meal_order_items', 'mealOrderId');
        $this->createIndex('idx-meal_order_items-dishId', 'meal_order_items', 'dishId');
        $this->createIndex('idx-meal_order_items-deleted_at', 'meal_order_items', 'deleted_at');
        $this->createIndex('idx-meal_order_items-unique', 'meal_order_items', ['mealOrderId', 'dishId'], true);

        $this->addForeignKey('fk-meal_order_items-mealOrderId', 'meal_order_items', 'mealOrderId', 'meal_orders', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-meal_order_items-dishId', 'meal_order_items', 'dishId', 'dishes', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-meal_order_items-userId', 'meal_order_items', 'userId', 'user', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-meal_order_items-deleted_by', 'meal_order_items', 'deleted_by', 'user', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('meal_order_items');
        $this->dropTable('meal_orders');
        $this->dropTable('dishes');
    }
}
