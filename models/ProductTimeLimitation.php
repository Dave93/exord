<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "product_time_limitation".
 *
 * @property string $productId
 * @property string $startTime
 * @property string $endTime
 */
class ProductTimeLimitation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_time_limitation';
    }

    /**
     * {@inheritdoc}
     */
    public static function primaryKey()
    {
        return ['productId'];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['productId', 'startTime', 'endTime'], 'required'],
            [['productId'], 'string', 'max' => 255],
            [['startTime', 'endTime'], 'date', 'format' => 'php:H:i'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'productId' => 'Продукт',
            'startTime' => 'Время начала',
            'endTime' => 'Время окончания',
        ];
    }


    public static function getList()
    {
        return ArrayHelper::map(self::find()->all(), 'productId', function($model) {
            return $model->product->name . ' (' . $model->startTime . ' - ' . $model->endTime . ')';
        });
    }
    
    /**
     * Gets the product associated with this limitation
     */
    public function getProduct()
    {
        return $this->hasOne(Products::class, ['id' => 'productId']);
    }
}
