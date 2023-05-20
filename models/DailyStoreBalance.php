<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "daily_store_balance".
 *
 * @property string $created_at
 * @property int $id
 * @property string $store_id
 * @property string $store_name
 */
class DailyStoreBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'daily_store_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at'], 'safe'],
            [['store_id', 'store_name'], 'required'],
            [['store_id'], 'string', 'max' => 36],
            [['store_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'created_at' => 'Created At',
            'id' => 'ID',
            'store_id' => 'Store ID',
            'store_name' => 'Store Name',
        ];
    }

//    public function updateBalance()
//    {
//        $iiko = new Iiko();
//        $data = $iiko->getReport($this->id);
//        if (!empty($data)) {
//            $i = 0;
//            StockBalance::deleteAll();
//            $sql = "insert into stock_balance(store,product,amount,sum) values";
//            foreach ($data as $row) {
//                $i++;
//                $sql .= "('{$row['store']}','{$row['product']}',{$row['amount']},{$row['sum']}),";
//            }
//            $sql = substr($sql, 0, -1);
//            Yii::$app->db->createCommand($sql)->execute();
//        }
//        return true;
//    }
}
