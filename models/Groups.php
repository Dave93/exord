<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "groups".
 *
 * @property string $id
 * @property string $departmentId
 * @property string $name
 * @property string $syncDate
 */
class Groups extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'groups';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['syncDate'], 'safe'],
            [['id', 'departmentId'], 'string', 'max' => 36],
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
            'departmentId' => 'Департамент',
            'name' => 'Название',
            'syncDate' => 'Дата синхронизации',
        ];
    }

    public static function getList()
    {
        return ArrayHelper::map(self::find()->orderBy(['name' => SORT_ASC])->asArray()->all(), 'id', 'name');
    }
}
