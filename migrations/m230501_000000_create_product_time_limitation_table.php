<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_time_limitation}}`.
 */
class m230501_000000_create_product_time_limitation_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%product_time_limitation}}', [
            'productId' => $this->string(255)->notNull(),
            'startTime' => $this->string(5)->notNull(),
            'endTime' => $this->string(5)->notNull(),
        ]);

        // Add primary key
        $this->addPrimaryKey('pk-product_time_limitation', '{{%product_time_limitation}}', 'productId');

        // Add foreign key
        $this->addForeignKey(
            'fk-product_time_limitation-productId',
            '{{%product_time_limitation}}',
            'productId',
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
        $this->dropForeignKey('fk-product_time_limitation-productId', '{{%product_time_limitation}}');
        $this->dropTable('{{%product_time_limitation}}');
    }
} 