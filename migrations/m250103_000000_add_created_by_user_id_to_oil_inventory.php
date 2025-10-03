<?php

use yii\db\Migration;

/**
 * Handles adding created_by_user_id to table `{{%oil_inventory}}`.
 */
class m250103_000000_add_created_by_user_id_to_oil_inventory extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%oil_inventory}}', 'created_by_user_id', $this->integer()->comment('ID пользователя, создавшего запись'));

        // Добавляем индекс
        $this->createIndex('idx-oil_inventory-created_by_user_id', '{{%oil_inventory}}', 'created_by_user_id');

        // Добавляем внешний ключ
        $this->addForeignKey(
            'fk-oil_inventory-created_by_user_id',
            '{{%oil_inventory}}',
            'created_by_user_id',
            '{{%users}}',
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
        $this->dropForeignKey('fk-oil_inventory-created_by_user_id', '{{%oil_inventory}}');

        // Удаляем индекс
        $this->dropIndex('idx-oil_inventory-created_by_user_id', '{{%oil_inventory}}');

        // Удаляем колонку
        $this->dropColumn('{{%oil_inventory}}', 'created_by_user_id');
    }
}
