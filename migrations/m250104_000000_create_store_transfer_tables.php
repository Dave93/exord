<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%store_transfers}}`.
 */
class m250104_000000_create_store_transfer_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем главную таблицу заявок на перемещение
        $this->createTable('{{%store_transfers}}', [
            'id' => $this->primaryKey(),
            'request_store_id' => $this->string(36)->notNull()->comment('ID магазина-заказчика (UUID)'),
            'created_by' => $this->integer()->notNull()->comment('ID пользователя, создавшего заявку'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Дата создания'),
            'status' => $this->string(20)->notNull()->defaultValue('new')->comment('Статус (new, in_progress, completed, cancelled)'),
            'comment' => $this->text()->comment('Комментарий'),
        ]);

        // Создаем таблицу позиций перемещения (группировка по филиалам-источникам)
        $this->createTable('{{%store_transfer_items}}', [
            'id' => $this->primaryKey(),
            'transfer_id' => $this->integer()->notNull()->comment('ID заявки на перемещение'),
            'source_store_id' => $this->string(36)->notNull()->comment('ID магазина-источника (UUID)'),
            'product_id' => $this->string(36)->notNull()->comment('ID продукта (UUID)'),
            'requested_quantity' => $this->decimal(10, 2)->notNull()->comment('Запрашиваемое количество'),
            'approved_quantity' => $this->decimal(10, 2)->comment('Утвержденное количество'),
            'transferred_quantity' => $this->decimal(10, 2)->comment('Фактически переданное количество'),
            'item_status' => $this->string(20)->notNull()->defaultValue('pending')->comment('Статус позиции (pending, approved, rejected, transferred)'),
        ]);

        // Индексы для главной таблицы
        $this->createIndex('idx-store_transfers-request_store_id', '{{%store_transfers}}', 'request_store_id');
        $this->createIndex('idx-store_transfers-status', '{{%store_transfers}}', 'status');
        $this->createIndex('idx-store_transfers-created_at', '{{%store_transfers}}', 'created_at');
        $this->createIndex('idx-store_transfers-created_by', '{{%store_transfers}}', 'created_by');

        // Индексы для таблицы позиций
        $this->createIndex('idx-store_transfer_items-transfer_id', '{{%store_transfer_items}}', 'transfer_id');
        $this->createIndex('idx-store_transfer_items-source_store_id', '{{%store_transfer_items}}', 'source_store_id');
        $this->createIndex('idx-store_transfer_items-product_id', '{{%store_transfer_items}}', 'product_id');
        $this->createIndex('idx-store_transfer_items-item_status', '{{%store_transfer_items}}', 'item_status');

        // Внешние ключи для главной таблицы
        $this->addForeignKey(
            'fk-store_transfers-request_store_id',
            '{{%store_transfers}}',
            'request_store_id',
            '{{%stores}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-store_transfers-created_by',
            '{{%store_transfers}}',
            'created_by',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Внешние ключи для таблицы позиций
        $this->addForeignKey(
            'fk-store_transfer_items-transfer_id',
            '{{%store_transfer_items}}',
            'transfer_id',
            '{{%store_transfers}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-store_transfer_items-source_store_id',
            '{{%store_transfer_items}}',
            'source_store_id',
            '{{%stores}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-store_transfer_items-product_id',
            '{{%store_transfer_items}}',
            'product_id',
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
        // Удаляем внешние ключи таблицы позиций
        $this->dropForeignKey('fk-store_transfer_items-transfer_id', '{{%store_transfer_items}}');
        $this->dropForeignKey('fk-store_transfer_items-source_store_id', '{{%store_transfer_items}}');
        $this->dropForeignKey('fk-store_transfer_items-product_id', '{{%store_transfer_items}}');

        // Удаляем индексы таблицы позиций
        $this->dropIndex('idx-store_transfer_items-transfer_id', '{{%store_transfer_items}}');
        $this->dropIndex('idx-store_transfer_items-source_store_id', '{{%store_transfer_items}}');
        $this->dropIndex('idx-store_transfer_items-product_id', '{{%store_transfer_items}}');
        $this->dropIndex('idx-store_transfer_items-item_status', '{{%store_transfer_items}}');

        // Удаляем таблицу позиций
        $this->dropTable('{{%store_transfer_items}}');

        // Удаляем внешние ключи главной таблицы
        $this->dropForeignKey('fk-store_transfers-request_store_id', '{{%store_transfers}}');
        $this->dropForeignKey('fk-store_transfers-created_by', '{{%store_transfers}}');

        // Удаляем индексы главной таблицы
        $this->dropIndex('idx-store_transfers-request_store_id', '{{%store_transfers}}');
        $this->dropIndex('idx-store_transfers-status', '{{%store_transfers}}');
        $this->dropIndex('idx-store_transfers-created_at', '{{%store_transfers}}');
        $this->dropIndex('idx-store_transfers-created_by', '{{%store_transfers}}');

        // Удаляем главную таблицу
        $this->dropTable('{{%store_transfers}}');
    }
}
