<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "dishes".
 *
 * @property int $id
 * @property string $name
 * @property string $unit
 * @property int $active
 */
class Dishes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dishes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['active'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['unit'], 'string', 'max' => 20],
            [['unit'], 'default', 'value' => 'шт'],
            [['active'], 'default', 'value' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'unit' => 'Ед. изм.',
            'active' => 'Активно',
        ];
    }

    /**
     * Список активных блюд [id => name]
     */
    public static function getList()
    {
        $data = self::find()->where(['active' => 1])->orderBy('name')->all();
        return ArrayHelper::map($data, 'id', 'name');
    }

    /**
     * Все активные блюда для формы заказа
     */
    public static function getActiveDishes()
    {
        return self::find()->where(['active' => 1])->orderBy('name')->all();
    }
}
