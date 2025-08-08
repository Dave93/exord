<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_stock".
 *
 * @property int $product_id
 * @property string $stock_id
 */
class ProductStock extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_stock';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'integer'],
            [['stock_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'stock_id' => 'Stock ID',
        ];
    }
}
