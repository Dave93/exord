<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "terminals".
 *
 * @property string $name
 * @property string $address
 * @property string $external_id
 * @property string $latitude
 * @property string $longitude
 * @property int $id
 */
class Terminals extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'terminals';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'address', 'external_id', 'latitude', 'longitude'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Name',
            'address' => 'Address',
            'external_id' => 'External ID',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'id' => 'ID',
        ];
    }

    public static function getList()
    {
        return ArrayHelper::map(self::find()->orderBy(['name' => SORT_ASC])->asArray()->all(), 'id', 'name');
    }
}
