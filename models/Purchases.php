<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "purchases".
 *
 * @property string $date
 * @property string $supplierId
 * @property string $productId
 * @property string $quantity
 * @property string $purchaseQuantity
 * @property string $price
 * @property string $addDate
 * @property string $author
 */
class Purchases extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'purchases';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date', 'supplierId', 'productId', 'quantity'], 'required'],
            [['date', 'addDate'], 'safe'],
            [['quantity', 'purchaseQuantity', 'price'], 'number'],
            [['supplierId', 'productId'], 'string', 'max' => 36],
            [['author'], 'string', 'max' => 255],
            [['date', 'supplierId', 'productId'], 'unique', 'targetAttribute' => ['date', 'supplierId', 'productId']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'date' => 'Date',
            'supplierId' => 'Supplier ID',
            'productId' => 'Product ID',
            'quantity' => 'Quantity',
            'purchaseQuantity' => 'Purchase Quantity',
            'price' => 'Price',
            'addDate' => 'Add Date',
            'author' => 'Author',
        ];
    }
}
