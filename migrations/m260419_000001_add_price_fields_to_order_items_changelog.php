<?php

use yii\db\Migration;

/**
 * Adds old_price / new_price columns to order_items_changelog so that
 * market_total_price edits can be logged alongside quantity changes.
 */
class m260419_000001_add_price_fields_to_order_items_changelog extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%order_items_changelog}}',
            'old_price',
            $this->decimal(12, 2)->null()->comment('Старая сумма (для изменений цены)')
        );
        $this->addColumn(
            '{{%order_items_changelog}}',
            'new_price',
            $this->decimal(12, 2)->null()->comment('Новая сумма (для изменений цены)')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_items_changelog}}', 'new_price');
        $this->dropColumn('{{%order_items_changelog}}', 'old_price');
    }
}
