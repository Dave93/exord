<?php

use yii\db\Migration;

/**
 * Adds market_total_quantity column to order_items. Holds the total
 * quantity actually purchased at the bazar for the position (independent
 * of the ordered and fact-received quantities).
 */
class m260423_000000_add_market_total_quantity_to_order_items extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%order_items}}',
            'market_total_quantity',
            $this->decimal(12, 3)->null()->comment('Количество купленного с базара по позиции')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_items}}', 'market_total_quantity');
    }
}
