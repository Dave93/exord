<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "suppliers".
 *
 * @property string $id
 * @property string $name
 * @property int $deleted
 * @property int $supplier
 * @property int $employee
 * @property int $client
 * @property string $syncDate
 */
class Suppliers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suppliers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['deleted', 'supplier', 'employee', 'client'], 'integer'],
            [['syncDate'], 'safe'],
            [['id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 255],
            [['id'], 'unique'],
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
            'deleted' => 'Удален',
            'supplier' => 'Поставщик',
            'employee' => 'Работник',
            'client' => 'Клиент',
            'syncDate' => 'Дата синх.',
        ];
    }

    public static function getList()
    {
        return ArrayHelper::map(self::find()->orderBy(['name' => SORT_ASC])->asArray()->all(), 'id', 'name');
    }
}
