<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_writeoffs".
 *
 * @property int $id
 * @property string $store_id ID магазина (UUID)
 * @property int $created_by ID пользователя, создавшего списание
 * @property string $created_at Дата создания
 * @property string $status Статус (new, approved)
 * @property int|null $approved_by ID пользователя, утвердившего списание
 * @property string|null $approved_at Дата утверждения
 * @property string|null $comment Комментарий
 *
 * @property Stores $store
 * @property User $createdBy
 * @property User $approvedBy
 * @property ProductWriteoffItem[] $items
 */
class ProductWriteoff extends \yii\db\ActiveRecord
{
    const STATUS_NEW = 'new';
    const STATUS_APPROVED = 'approved';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_writeoffs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['store_id', 'created_by'], 'required'],
            [['store_id'], 'string', 'max' => 36],
            [['created_by', 'approved_by'], 'integer'],
            [['created_at', 'approved_at'], 'safe'],
            [['comment'], 'string'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_NEW, self::STATUS_APPROVED]],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Stores::class, 'targetAttribute' => ['store_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['approved_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['approved_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Магазин',
            'created_by' => 'Создал',
            'created_at' => 'Дата создания',
            'status' => 'Статус',
            'approved_by' => 'Утвердил',
            'approved_at' => 'Дата утверждения',
            'comment' => 'Комментарий',
        ];
    }

    /**
     * Gets query for [[Store]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Stores::class, ['id' => 'store_id']);
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
     * Gets query for [[ApprovedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getApprovedBy()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
    }

    /**
     * Gets query for [[Items]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(ProductWriteoffItem::class, ['writeoff_id' => 'id']);
    }

    /**
     * Получить читаемое название статуса
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_NEW => 'Новое',
            self::STATUS_APPROVED => 'Утверждено',
        ];

        return isset($labels[$this->status]) ? $labels[$this->status] : $this->status;
    }

    /**
     * Утвердить списание
     * @param array|null $approvedCounts Массив утвержденных количеств по позициям ['item_id' => 'approved_count']
     * @return bool
     */
    public function approve($approvedCounts = null)
    {
        if ($this->status === self::STATUS_APPROVED) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Обновляем утвержденные количества в позициях
            if ($approvedCounts && is_array($approvedCounts)) {
                foreach ($this->items as $item) {
                    if (isset($approvedCounts[$item->id])) {
                        $item->approved_count = $approvedCounts[$item->id];
                        $item->save(false);
                    } else {
                        $item->approved_count = $item->count;
                        $item->save(false);
                    }
                }
            } else {
                // Утверждаем все с исходным количеством
                foreach ($this->items as $item) {
                    $item->approved_count = $item->count;
                    $item->save(false);
                }
            }

            $this->status = self::STATUS_APPROVED;
            $this->approved_by = Yii::$app->user->id;
            $this->approved_at = date('Y-m-d H:i:s');

            if ($this->save(false)) {
                $transaction->commit();
                return true;
            }

            $transaction->rollBack();
            return false;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
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
     * Получить общее количество для списания
     * @return float
     */
    public function getTotalCount()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->count;
        }
        return $total;
    }

    /**
     * Проверить, можно ли редактировать списание
     * @return bool
     */
    public function canEdit()
    {
        return $this->status === self::STATUS_NEW;
    }
}
