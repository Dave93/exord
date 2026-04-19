<?php

use yii\db\Migration;

/**
 * Adds market_total_price column to order_items. Holds the total amount
 * paid for a bazar item in a closed order (not per-unit price).
 */
class m260419_000000_add_market_total_price_to_order_items extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%order_items}}',
            'market_total_price',
            $this->decimal(12, 2)->null()->comment('Общая сумма за базарную позицию')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_items}}', 'market_total_price');
    }
}
