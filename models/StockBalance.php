<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "stock_balance".
 *
 * @property string $store
 * @property string $product
 * @property double $amount
 * @property string $sum
 */
class StockBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stock_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['store', 'product'], 'required'],
            [['amount', 'sum'], 'number'],
            [['store', 'product'], 'string', 'max' => 36],
            [['store', 'product'], 'unique', 'targetAttribute' => ['store', 'product']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'store' => 'Store',
            'product' => 'Product',
            'amount' => 'Amount',
            'sum' => 'Sum',
        ];
    }
}
