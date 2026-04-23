<?php

use yii\db\Migration;

/**
 * Pivot table linking buyer users to branches they are allowed to work
 * with in the market prices section. Empty set means "all branches".
 */
class m260423_000001_create_user_buyer_stores extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_buyer_stores}}', [
            'user_id' => $this->integer()->notNull(),
            'store_id' => $this->string(36)->notNull(),
            'PRIMARY KEY(user_id, store_id)',
        ]);

        $this->createIndex('idx-user_buyer_stores-user_id', '{{%user_buyer_stores}}', 'user_id');
        $this->createIndex('idx-user_buyer_stores-store_id', '{{%user_buyer_stores}}', 'store_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_buyer_stores}}');
    }
}
