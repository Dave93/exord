<?php

namespace app\models;

class DailyStoreProduct extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'daily_store_product';
    }

    public function rules()
    {
        return [
            [['store_id'], 'required']
        ];
    }

    public function attributeLabels()
    {
        return [
            'store_id' => 'Склад',
            'product_id' => 'Ид продукта',
            'quantity' => 'Количество',
        ];
    }
}