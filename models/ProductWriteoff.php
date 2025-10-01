<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_writeoffs".
 *
 * @property int $id
 * @property int $store_id ID магазина
 * @property int $product_id ID продукта
 * @property float $count Количество списания
 * @property string $created_at Дата создания
 * @property float|null $approved_count Утвержденное количество
 * @property string $status Статус (new, approved)
 * @property int|null $approved_by ID пользователя, утвердившего списание
 * @property string|null $approved_at Дата утверждения
 *
 * @property Store $store
 * @property Product $product
 * @property User $approvedBy
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
            [['store_id', 'product_id', 'count'], 'required'],
            [['store_id', 'product_id', 'approved_by'], 'integer'],
            [['count', 'approved_count'], 'number', 'min' => 0],
            [['created_at', 'approved_at'], 'safe'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_NEW, self::STATUS_APPROVED]],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
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
            'product_id' => 'Продукт',
            'count' => 'Количество',
            'created_at' => 'Дата создания',
            'approved_count' => 'Утвержденное количество',
            'status' => 'Статус',
            'approved_by' => 'Утвердил',
            'approved_at' => 'Дата утверждения',
        ];
    }

    /**
     * Gets query for [[Store]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Store::class, ['id' => 'store_id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
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
     * @param float|null $approvedCount Утвержденное количество (если null, используется count)
     * @return bool
     */
    public function approve($approvedCount = null)
    {
        if ($this->status === self::STATUS_APPROVED) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_count = $approvedCount !== null ? $approvedCount : $this->count;
        $this->approved_by = Yii::$app->user->id;
        $this->approved_at = date('Y-m-d H:i:s');

        return $this->save();
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
