<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "orders_init_queue".
 *
 * @property string $org_id
 * @property string $t_id
 * @property string $corellation_id
 * @property int $id
 */
class OrdersInitQueue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orders_init_queue';
    }

    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['org_id', 't_id', 'corellation_id'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'org_id' => 'Org',
            't_id' => 'Terminal',
            'corellation_id' => 'Corellation ID'
        ];
    }
}