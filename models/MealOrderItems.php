<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "meal_order_items".
 *
 * @property int $id
 * @property int $mealOrderId
 * @property int $dishId
 * @property string $quantity
 * @property int $userId
 * @property string $deleted_at
 * @property int $deleted_by
 *
 * @property Dishes $dish
 * @property User $deletedBy
 */
class MealOrderItems extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'meal_order_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mealOrderId', 'dishId', 'quantity'], 'required'],
            [['mealOrderId', 'dishId', 'userId', 'deleted_by'], 'integer'],
            [['quantity'], 'number'],
            [['deleted_at'], 'safe'],
            [['mealOrderId', 'dishId'], 'unique', 'targetAttribute' => ['mealOrderId', 'dishId']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mealOrderId' => 'Заказ',
            'dishId' => 'Блюдо',
            'quantity' => 'Количество',
            'userId' => 'Пользователь',
            'deleted_at' => 'Дата удаления',
            'deleted_by' => 'Удалил',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDish()
    {
        return $this->hasOne(Dishes::className(), ['id' => 'dishId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeletedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'deleted_by']);
    }

    /**
     * Переопределяем find() для автоматического исключения удалённых записей
     */
    public static function find()
    {
        return parent::find()->where(['meal_order_items.deleted_at' => null]);
    }

    /**
     * Возвращает query для всех записей, включая удалённые
     */
    public static function findWithDeleted()
    {
        return parent::find();
    }

    /**
     * Возвращает query для только удалённых записей
     */
    public static function findOnlyDeleted()
    {
        return parent::find()->where(['not', ['meal_order_items.deleted_at' => null]]);
    }

    /**
     * Soft delete
     */
    public function delete()
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        $this->deleted_by = Yii::$app->user->id;
        return $this->save(false);
    }

    /**
     * Физическое удаление
     */
    public function forceDelete()
    {
        return parent::delete();
    }

    /**
     * Восстановление удалённой записи
     */
    public function restore()
    {
        $this->deleted_at = null;
        $this->deleted_by = null;
        return $this->save(false);
    }

    /**
     * Проверяет, удалена ли запись
     */
    public function isDeleted()
    {
        return $this->deleted_at !== null;
    }
}
