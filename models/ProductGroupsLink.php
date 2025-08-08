<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_groups_link".
 *
 * @property int $productGroupId
 * @property string $productId
 */
class ProductGroupsLink extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_groups_link';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['productGroupId', 'productId'], 'required'],
            [['productGroupId'], 'integer'],
            [['productId'], 'string', 'max' => 36],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'productGroupId' => 'Product Group ID',
            'productId' => 'Product ID',
        ];
    }
}
