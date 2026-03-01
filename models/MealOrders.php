<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "meal_orders".
 *
 * @property int $id
 * @property int $userId
 * @property string $storeId
 * @property string $date
 * @property string $addDate
 * @property string $comment
 * @property int $state
 * @property int $editable
 * @property int $is_locked
 * @property string $deleted_at
 * @property int $deleted_by
 *
 * @property User $user
 * @property Stores $store
 * @property MealOrderItems[] $items
 * @property User $deletedBy
 */
class MealOrders extends \yii\db\ActiveRecord
{
    public static $states = [
        0 => "Новый",
        1 => "Отправлен",
        2 => "Завершен",
        3 => "Проверка офисом",
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'meal_orders';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userId', 'date'], 'required'],
            [['userId', 'state', 'editable', 'deleted_by'], 'integer'],
            [['date', 'addDate', 'deleted_at'], 'safe'],
            [['comment'], 'string'],
            [['storeId'], 'string', 'max' => 36],
            [['is_locked'], 'boolean'],
            [['userId'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userId' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Номер заказа',
            'userId' => 'Заказчик',
            'storeId' => 'Филиал',
            'date' => 'Дата',
            'comment' => 'Комментарий',
            'addDate' => 'Добавлен в',
            'state' => 'Статус',
            'editable' => 'Редактируемый',
            'is_locked' => 'Заблокирован',
            'deleted_at' => 'Удалено в',
            'deleted_by' => 'Удалено пользователем',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeletedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'deleted_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(MealOrderItems::className(), ['mealOrderId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Stores::className(), ['id' => 'storeId']);
    }

    /**
     * Проверяет, можно ли закрыть заказ (есть ли позиции с quantity > 0)
     */
    public function canClose()
    {
        $count = MealOrderItems::find()
            ->where(['mealOrderId' => $this->id])
            ->andWhere(['>', 'quantity', 0])
            ->count();
        return $count > 0;
    }
}
