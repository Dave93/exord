<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "store_transfers".
 *
 * @property int $id
 * @property string $request_store_id ID магазина-заказчика (UUID)
 * @property int $created_by ID пользователя, создавшего заявку
 * @property string $created_at Дата создания
 * @property string $status Статус (new, in_progress, completed, cancelled)
 * @property string|null $comment Комментарий
 *
 * @property Stores $requestStore
 * @property User $createdBy
 * @property StoreTransferItem[] $items
 */
class StoreTransfer extends \yii\db\ActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'store_transfers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_store_id', 'created_by'], 'required'],
            [['request_store_id'], 'string', 'max' => 36],
            [['created_by'], 'integer'],
            [['created_at'], 'safe'],
            [['comment'], 'string'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED]],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['request_store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Stores::class, 'targetAttribute' => ['request_store_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'request_store_id' => 'Магазин-заказчик',
            'created_by' => 'Создал',
            'created_at' => 'Дата создания',
            'status' => 'Статус',
            'comment' => 'Комментарий',
        ];
    }

    /**
     * Gets query for [[RequestStore]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequestStore()
    {
        return $this->hasOne(Stores::class, ['id' => 'request_store_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Items]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(StoreTransferItem::class, ['transfer_id' => 'id']);
    }

    /**
     * Получить читаемое название статуса
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_NEW => 'Новая',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_CANCELLED => 'Отменена',
        ];

        return isset($labels[$this->status]) ? $labels[$this->status] : $this->status;
    }

    /**
     * Получить список филиалов-источников
     * @return array
     */
    public function getSourceStores()
    {
        $storeIds = [];
        foreach ($this->items as $item) {
            if (!in_array($item->source_store_id, $storeIds)) {
                $storeIds[] = $item->source_store_id;
            }
        }

        return Stores::find()->where(['id' => $storeIds])->all();
    }

    /**
     * Получить позиции по филиалу-источнику
     * @param string $sourceStoreId
     * @return StoreTransferItem[]
     */
    public function getItemsBySourceStore($sourceStoreId)
    {
        return StoreTransferItem::find()
            ->where(['transfer_id' => $this->id, 'source_store_id' => $sourceStoreId])
            ->all();
    }

    /**
     * Получить общее количество позиций
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->items);
    }

    /**
     * Получить общее запрашиваемое количество
     * @return float
     */
    public function getTotalRequestedQuantity()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->requested_quantity;
        }
        return $total;
    }

    /**
     * Проверить, можно ли редактировать заявку
     * @return bool
     */
    public function canEdit()
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Проверить, можно ли отменить заявку
     * @return bool
     */
    public function canCancel()
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Отменить заявку
     * @return bool
     */
    public function cancel()
    {
        if (!$this->canCancel()) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        return $this->save(false);
    }

    /**
     * Перевести в статус "В работе"
     * @return bool
     */
    public function setInProgress()
    {
        if ($this->status !== self::STATUS_NEW) {
            return false;
        }

        $this->status = self::STATUS_IN_PROGRESS;
        return $this->save(false);
    }

    /**
     * Завершить заявку
     * @return bool
     */
    public function complete()
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        return $this->save(false);
    }
}
