<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "departments".
 *
 * @property string $id
 * @property string $parentId
 * @property string $name
 * @property string $type
 * @property string $syncDate
 */
class Departments extends \yii\db\ActiveRecord
{
    public static $types = [
        "CORPORATION" => "Корпорация",
        "JURPERSON" => "Юридическое лицо",
        "ORGDEVELOPMENT" => "Структурное подразделение",
        "DEPARTMENT" => "Торговое предприятие",
        "MANUFACTURE" => "Производство",
        "CENTRALSTORE" => "Центральный склад",
        "CENTRALOFFICE" => "Центральный офис",
        "SALEPOINT" => "Точка продаж",
        "STORE" => "Склад",
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'departments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['syncDate'], 'safe'],
            [['id', 'parentId'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
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
            'parentId' => 'Родительская группа',
            'name' => 'Название',
            'type' => 'Тип',
            'syncDate' => 'Дата синхронизации',
        ];
    }
}
