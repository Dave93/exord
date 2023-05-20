<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "zone".
 *
 * @property int $id
 * @property string $name
 * @property string $date
 * @property string $author
 */
class Zone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'zone';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['name', 'author'], 'string', 'max' => 255],
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
            'date' => 'Дата',
            'author' => 'Автор',
        ];
    }

    public static function getList()
    {
        return ArrayHelper::map(self::find()->orderBy(['name' => SORT_ASC])->asArray()->all(), 'name', 'name');
    }
}
