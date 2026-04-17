<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "meal_order_items_changelog".
 *
 * @property int $id
 * @property int $mealOrderId ID заказа блюд
 * @property int $dishId ID блюда
 * @property string $action Действие: added, deleted, updated, restored
 * @property float|null $old_quantity Старое количество
 * @property float|null $new_quantity Новое количество
 * @property int $userId ID пользователя, который внёс изменение
 * @property string $created_at Дата и время изменения
 *
 * @property MealOrders $mealOrder
 * @property Dishes $dish
 * @property User $user
 */
class MealOrderItemsChangelog extends \yii\db\ActiveRecord
{
    const ACTION_ADDED = 'added';
    const ACTION_DELETED = 'deleted';
    const ACTION_UPDATED = 'updated';
    const ACTION_RESTORED = 'restored';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'meal_order_items_changelog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mealOrderId', 'dishId', 'action', 'userId'], 'required'],
            [['mealOrderId', 'dishId', 'userId'], 'integer'],
            [['old_quantity', 'new_quantity'], 'number'],
            [['created_at'], 'safe'],
            [['action'], 'string', 'max' => 20],
            [['action'], 'in', 'range' => [self::ACTION_ADDED, self::ACTION_DELETED, self::ACTION_UPDATED, self::ACTION_RESTORED]],
            [['mealOrderId'], 'exist', 'skipOnError' => true, 'targetClass' => MealOrders::class, 'targetAttribute' => ['mealOrderId' => 'id']],
            [['dishId'], 'exist', 'skipOnError' => true, 'targetClass' => Dishes::class, 'targetAttribute' => ['dishId' => 'id']],
            [['userId'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userId' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mealOrderId' => 'ID заказа блюд',
            'dishId' => 'Блюдо',
            'action' => 'Действие',
            'old_quantity' => 'Старое количество',
            'new_quantity' => 'Новое количество',
            'userId' => 'Пользователь',
            'created_at' => 'Дата изменения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMealOrder()
    {
        return $this->hasOne(MealOrders::class, ['id' => 'mealOrderId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDish()
    {
        return $this->hasOne(Dishes::class, ['id' => 'dishId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * Человекочитаемое название действия
     *
     * @return string
     */
    public function getActionLabel()
    {
        $labels = [
            self::ACTION_ADDED => 'Добавлено',
            self::ACTION_DELETED => 'Удалено',
            self::ACTION_UPDATED => 'Изменено',
            self::ACTION_RESTORED => 'Восстановлено',
        ];

        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Создаёт запись об изменении позиции заказа блюд
     *
     * @param int $mealOrderId
     * @param int $dishId
     * @param string $action
     * @param float|null $oldQuantity
     * @param float|null $newQuantity
     * @param int|null $userId
     * @return bool
     */
    public static function log($mealOrderId, $dishId, $action, $oldQuantity = null, $newQuantity = null, $userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        $changelog = new self();
        $changelog->mealOrderId = $mealOrderId;
        $changelog->dishId = $dishId;
        $changelog->action = $action;
        $changelog->old_quantity = $oldQuantity;
        $changelog->new_quantity = $newQuantity;
        $changelog->userId = $userId;

        return $changelog->save();
    }

    /**
     * История изменений для конкретного заказа блюд
     *
     * @param int $mealOrderId
     * @return MealOrderItemsChangelog[]
     */
    public static function getMealOrderHistory($mealOrderId)
    {
        return self::find()
            ->where(['mealOrderId' => $mealOrderId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * История изменений для конкретной позиции
     *
     * @param int $mealOrderId
     * @param int $dishId
     * @return MealOrderItemsChangelog[]
     */
    public static function getItemHistory($mealOrderId, $dishId)
    {
        return self::find()
            ->where(['mealOrderId' => $mealOrderId, 'dishId' => $dishId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
}
