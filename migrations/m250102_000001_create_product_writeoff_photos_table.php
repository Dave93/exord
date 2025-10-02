<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_writeoff_photos}}`.
 */
class m250102_000001_create_product_writeoff_photos_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%product_writeoff_photos}}', [
            'id' => $this->primaryKey(),
            'writeoff_id' => $this->integer()->notNull()->comment('ID списания'),
            'filename' => $this->string(255)->notNull()->comment('Имя файла'),
            'uploaded_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата загрузки'),
        ]);

        // Создаем индекс
        $this->createIndex(
            'idx-product_writeoff_photos-writeoff_id',
            '{{%product_writeoff_photos}}',
            'writeoff_id'
        );

        // Добавляем внешний ключ
        $this->addForeignKey(
            'fk-product_writeoff_photos-writeoff_id',
            '{{%product_writeoff_photos}}',
            'writeoff_id',
            '{{%product_writeoffs}}',
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
        // Удаляем внешний ключ
        $this->dropForeignKey('fk-product_writeoff_photos-writeoff_id', '{{%product_writeoff_photos}}');

        // Удаляем индекс
        $this->dropIndex('idx-product_writeoff_photos-writeoff_id', '{{%product_writeoff_photos}}');

        // Удаляем таблицу
        $this->dropTable('{{%product_writeoff_photos}}');
    }
}
