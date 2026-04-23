<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "order_items_changelog".
 *
 * @property int $id
 * @property int $orderId ID заказа
 * @property string $productId ID продукта
 * @property string $action Действие: added, deleted, updated, restored
 * @property float|null $old_quantity Старое количество
 * @property float|null $new_quantity Новое количество
 * @property int $userId ID пользователя, который внёс изменение
 * @property string $created_at Дата и время изменения
 *
 * @property Orders $order
 * @property Products $product
 * @property User $user
 */
class OrderItemsChangelog extends \yii\db\ActiveRecord
{
    const ACTION_ADDED = 'added';
    const ACTION_DELETED = 'deleted';
    const ACTION_UPDATED = 'updated';
    const ACTION_RESTORED = 'restored';
    const ACTION_PRICE_UPDATED = 'price_updated';
    const ACTION_MARKET_QUANTITY_UPDATED = 'market_quantity_updated';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_items_changelog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orderId', 'productId', 'action', 'userId'], 'required'],
            [['orderId', 'userId'], 'integer'],
            [['old_quantity', 'new_quantity', 'old_price', 'new_price'], 'number'],
            [['created_at'], 'safe'],
            [['productId'], 'string', 'max' => 36],
            [['action'], 'string', 'max' => 20],
            [['action'], 'in', 'range' => [
                self::ACTION_ADDED,
                self::ACTION_DELETED,
                self::ACTION_UPDATED,
                self::ACTION_RESTORED,
                self::ACTION_PRICE_UPDATED,
                self::ACTION_MARKET_QUANTITY_UPDATED,
            ]],
            [['orderId'], 'exist', 'skipOnError' => true, 'targetClass' => Orders::class, 'targetAttribute' => ['orderId' => 'id']],
            [['productId'], 'exist', 'skipOnError' => true, 'targetClass' => Products::class, 'targetAttribute' => ['productId' => 'id']],
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
            'orderId' => 'ID заказа',
            'productId' => 'Продукт',
            'action' => 'Действие',
            'old_quantity' => 'Старое количество',
            'new_quantity' => 'Новое количество',
            'userId' => 'Пользователь',
            'created_at' => 'Дата изменения',
            'old_price' => 'Старая сумма',
            'new_price' => 'Новая сумма',
        ];
    }

    /**
     * Получает связанный заказ
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Orders::class, ['id' => 'orderId']);
    }

    /**
     * Получает связанный продукт
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::class, ['id' => 'productId']);
    }

    /**
     * Получает пользователя, внёсшего изменение
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * Получает человекочитаемое название действия
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
            self::ACTION_PRICE_UPDATED => 'Изменена сумма базара',
            self::ACTION_MARKET_QUANTITY_UPDATED => 'Изменено кол-во с базара',
        ];

        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Создает запись об изменении позиции заказа
     *
     * @param int $orderId
     * @param string $productId
     * @param string $action
     * @param float|null $oldQuantity
     * @param float|null $newQuantity
     * @param int $userId
     * @return bool
     */
    public static function log($orderId, $productId, $action, $oldQuantity = null, $newQuantity = null, $userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        $changelog = new self();
        $changelog->orderId = $orderId;
        $changelog->productId = $productId;
        $changelog->action = $action;
        $changelog->old_quantity = $oldQuantity;
        $changelog->new_quantity = $newQuantity;
        $changelog->userId = $userId;

        return $changelog->save();
    }

    /**
     * Logs a change of market_total_price on an order item.
     *
     * @param int $orderId
     * @param string $productId
     * @param float|null $oldPrice
     * @param float|null $newPrice
     * @param int|null $userId
     * @return bool
     */
    public static function logPriceChange($orderId, $productId, $oldPrice, $newPrice, $userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        $changelog = new self();
        $changelog->orderId = $orderId;
        $changelog->productId = $productId;
        $changelog->action = self::ACTION_PRICE_UPDATED;
        $changelog->old_price = $oldPrice;
        $changelog->new_price = $newPrice;
        $changelog->userId = $userId;

        return $changelog->save();
    }

    /**
     * Logs a change of market_total_quantity on an order item.
     *
     * @param int $orderId
     * @param string $productId
     * @param float|null $oldQuantity
     * @param float|null $newQuantity
     * @param int|null $userId
     * @return bool
     */
    public static function logMarketQuantityChange($orderId, $productId, $oldQuantity, $newQuantity, $userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        $changelog = new self();
        $changelog->orderId = $orderId;
        $changelog->productId = $productId;
        $changelog->action = self::ACTION_MARKET_QUANTITY_UPDATED;
        $changelog->old_quantity = $oldQuantity;
        $changelog->new_quantity = $newQuantity;
        $changelog->userId = $userId;

        return $changelog->save();
    }

    /**
     * Получает историю изменений для конкретного заказа
     *
     * @param int $orderId
     * @return OrderItemsChangelog[]
     */
    public static function getOrderHistory($orderId)
    {
        return self::find()
            ->where(['orderId' => $orderId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * Получает историю изменений для конкретной позиции
     *
     * @param int $orderId
     * @param string $productId
     * @return OrderItemsChangelog[]
     */
    public static function getItemHistory($orderId, $productId)
    {
        return self::find()
            ->where(['orderId' => $orderId, 'productId' => $productId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
}
