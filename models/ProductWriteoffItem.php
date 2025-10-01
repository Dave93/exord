<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_writeoff_items".
 *
 * @property int $id
 * @property int $writeoff_id ID списания
 * @property int $product_id ID продукта
 * @property float $count Количество списания
 * @property float|null $approved_count Утвержденное количество
 *
 * @property ProductWriteoff $writeoff
 * @property Products $product
 */
class ProductWriteoffItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_writeoff_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['writeoff_id', 'product_id', 'count'], 'required'],
            [['writeoff_id', 'product_id'], 'integer'],
            [['count', 'approved_count'], 'number', 'min' => 0.01],
            [['writeoff_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductWriteoff::class, 'targetAttribute' => ['writeoff_id' => 'id']],
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
            'writeoff_id' => 'ID списания',
            'product_id' => 'Продукт',
            'count' => 'Количество',
            'approved_count' => 'Утвержденное количество',
        ];
    }

    /**
     * Gets query for [[Writeoff]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWriteoff()
    {
        return $this->hasOne(ProductWriteoff::class, ['id' => 'writeoff_id']);
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
}
