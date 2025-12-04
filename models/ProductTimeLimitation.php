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

    /**
     * Checks if products can be ordered at the current time
     * @param array $productIds Array of product IDs to check
     * @return array Array of product IDs that are NOT allowed at current time
     */
    public static function getRestrictedProducts($productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $currentTime = date('H:i');
        $restrictedProducts = [];

        $limitations = self::find()
            ->where(['productId' => $productIds])
            ->all();

        foreach ($limitations as $limitation) {
            $startTime = $limitation->startTime;
            $endTime = $limitation->endTime;

            $isTimeAllowed = false;

            // If end time is less than start time (spans midnight)
            if ($endTime < $startTime) {
                $isTimeAllowed = ($currentTime >= $startTime || $currentTime < $endTime);
            } else {
                $isTimeAllowed = ($currentTime >= $startTime && $currentTime < $endTime);
            }

            if (!$isTimeAllowed) {
                $restrictedProducts[$limitation->productId] = [
                    'productId' => $limitation->productId,
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'productName' => $limitation->product ? $limitation->product->name : $limitation->productId
                ];
            }
        }

        return $restrictedProducts;
    }
}
