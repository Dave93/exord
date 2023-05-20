<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "stores".
 *
 * @property string $id
 * @property string $parentId
 * @property string $name
 * @property string $type
 * @property string $syncDate
 */
class Stores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stores';
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
            'name' => 'Наименование',
            'type' => 'Тип',
            'syncDate' => 'Дата синхронизации',
        ];
    }

    public static function getList()
    {
        return ArrayHelper::map(self::find()->orderBy(['name' => SORT_ASC])->asArray()->all(), 'id', 'name');
    }

    public function updateBalance()
    {
        $iiko = new Iiko();
        $data = $iiko->getReport($this->id);
        if (!empty($data)) {
            $i = 0;
            StockBalance::deleteAll();
            $sql = "insert into stock_balance(store,product,amount,sum) values";
            foreach ($data as $row) {
                $i++;
                $sql .= "('{$row['store']}','{$row['product']}',{$row['amount']},{$row['sum']}),";
            }
            $sql = substr($sql, 0, -1);
            Yii::$app->db->createCommand($sql)->execute();
        }
        return true;
    }
}
