<?php

namespace app\models;

use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tg_users".
 *
 * @property int $id
 * @property string $tg_id
 * @property int $user_id
 * @property int $active
 * @property string $phone
 * @property string $name
 *
 * @property User $user
 */
class TgUsers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tg_users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'active'], 'integer'],
            [['tg_id', 'phone', 'name'], 'string', 'max' => 50],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tg_id' => 'Tg ID',
            'user_id' => 'Пользователь',
            'active' => 'Активность',
            'phone' => 'Телефон',
            'name' => 'Имя',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }


    public static function getUsers() {
        $query = new Query();
        $data = $query->select("id,name")
            ->from("tg_users")
            ->orderBy("name")
            ->all();
        return ArrayHelper::map($data, 'id', 'name');
    }
}
