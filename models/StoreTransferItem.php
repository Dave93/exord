<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "store_transfer_items".
 *
 * @property int $id
 * @property int $transfer_id ID заявки на перемещение
 * @property string $source_store_id ID магазина-источника (UUID)
 * @property string $product_id ID продукта (UUID)
 * @property float $requested_quantity Запрашиваемое количество
 * @property float|null $approved_quantity Утвержденное количество
 * @property float|null $transferred_quantity Фактически переданное количество
 * @property string $item_status Статус позиции (pending, approved, rejected, transferred)
 *
 * @property StoreTransfer $transfer
 * @property Stores $sourceStore
 * @property Products $product
 */
class StoreTransferItem extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_TRANSFERRED = 'transferred';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'store_transfer_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transfer_id', 'source_store_id', 'product_id', 'requested_quantity'], 'required'],
            [['transfer_id'], 'integer'],
            [['source_store_id', 'product_id'], 'string', 'max' => 36],
            [['requested_quantity', 'approved_quantity', 'transferred_quantity'], 'number', 'min' => 0],
            [['item_status'], 'string', 'max' => 20],
            [['item_status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_TRANSFERRED]],
            [['item_status'], 'default', 'value' => self::STATUS_PENDING],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => StoreTransfer::class, 'targetAttribute' => ['transfer_id' => 'id']],
            [['source_store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Stores::class, 'targetAttribute' => ['source_store_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Products::class, 'targetAttribute' => ['product_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transfer_id' => 'ID заявки',
            'source_store_id' => 'Филиал-источник',
            'product_id' => 'Продукт',
            'requested_quantity' => 'Запрашиваемое количество',
            'approved_quantity' => 'Утвержденное количество',
            'transferred_quantity' => 'Переданное количество',
            'item_status' => 'Статус',
        ];
    }

    /**
     * Gets query for [[Transfer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer()
    {
        return $this->hasOne(StoreTransfer::class, ['id' => 'transfer_id']);
    }

    /**
     * Gets query for [[SourceStore]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSourceStore()
    {
        return $this->hasOne(Stores::class, ['id' => 'source_store_id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::class, ['id' => 'product_id']);
    }

    /**
     * Получить читаемое название статуса
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_APPROVED => 'Утверждено',
            self::STATUS_REJECTED => 'Отклонено',
            self::STATUS_TRANSFERRED => 'Передано',
        ];

        return isset($labels[$this->item_status]) ? $labels[$this->item_status] : $this->item_status;
    }

    /**
     * Утвердить позицию
     * @param float|null $approvedQuantity
     * @return bool
     */
    public function approve($approvedQuantity = null)
    {
        if ($this->item_status !== self::STATUS_PENDING) {
            return false;
        }

        $this->approved_quantity = $approvedQuantity !== null ? $approvedQuantity : $this->requested_quantity;
        $this->item_status = self::STATUS_APPROVED;

        return $this->save(false);
    }

    /**
     * Отклонить позицию
     * @return bool
     */
    public function reject()
    {
        if ($this->item_status !== self::STATUS_PENDING) {
            return false;
        }

        $this->item_status = self::STATUS_REJECTED;
        return $this->save(false);
    }

    /**
     * Отметить как переданное
     * @param float|null $transferredQuantity
     * @return bool
     */
    public function markTransferred($transferredQuantity = null)
    {
        if ($this->item_status !== self::STATUS_APPROVED) {
            return false;
        }

        $this->transferred_quantity = $transferredQuantity !== null ? $transferredQuantity : $this->approved_quantity;
        $this->item_status = self::STATUS_TRANSFERRED;

        return $this->save(false);
    }
}
