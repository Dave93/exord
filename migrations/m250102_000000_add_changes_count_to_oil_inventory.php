<?php

use yii\db\Migration;

/**
 * Handles adding changes_count to table `{{%oil_inventory}}`.
 */
class m250102_000000_add_changes_count_to_oil_inventory extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%oil_inventory}}', 'changes_count', $this->integer()->defaultValue(0)->comment('Количество изменений')->after('status'));

        // Создаем индекс для поля changes_count
        $this->createIndex('idx-oil_inventory-changes_count', '{{%oil_inventory}}', 'changes_count');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем индекс
        $this->dropIndex('idx-oil_inventory-changes_count', '{{%oil_inventory}}');

        // Удаляем колонку
        $this->dropColumn('{{%oil_inventory}}', 'changes_count');
    }
}
