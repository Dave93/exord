<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "write_off".
 *
 * @property int $id
 * @property string $video
 * @property string $date
 * @property string $order_number
 * @property string $customer_phone
 * @property int $write_price
 * @property int $delete_message_id
 * @property int $tg_user_id
 * @property int $user_id
 *
 * @property User $user
 * @property TgUsers $tgUser
 */
class WriteOff extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'write_off';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['write_price', 'delete_message_id', 'tg_user_id', 'user_id'], 'integer'],
            [['video'], 'string', 'max' => 250],
            [['comment'], 'string', 'max' => 300],
            [['order_number', 'customer_phone', 'blame'], 'string', 'max' => 50],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['tg_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => TgUsers::className(), 'targetAttribute' => ['tg_user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'video' => 'Видео',
            'date' => 'Дата',
            'order_number' => 'Номер заказа',
            'customer_phone' => 'Номер клиента',
            'write_price' => 'Сумма списания',
            'delete_message_id' => 'Delete Message ID',
            'tg_user_id' => 'Tg User ID',
            'user_id' => 'Пользователь',
            'comment' => 'Комментарий',
            'blame' => 'Виновник'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTgUser()
    {
        return $this->hasOne(TgUsers::className(), ['id' => 'tg_user_id']);
    }

    public static function getBlameDropdown()
    {
        /**
         * create array with blame from this string
         * cook - повар
         * senior-chef - старший повар
         * manager - менеджер
         * cashier - кассир
         * delivery - доставка
         * distribution - раздача
         * force-majeure - форс-мажор
         * warehouse - склад
         * customer - клиент
         * call-center - колл-центр
         */
        $blame = [
            'cook' => 'Повар',
            'senior-chef' => 'Старший повар',
            'manager' => 'Менеджер',
            'cashier' => 'Кассир',
            'delivery' => 'Доставка',
            'distribution' => 'Раздача',
            'force-majeure' => 'Форс-мажор',
            'warehouse' => 'Склад',
            'customer' => 'Клиент',
            'call-center' => 'Колл-центр',
        ];

        return $blame;
    }
}
