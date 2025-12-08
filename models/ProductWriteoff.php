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
 * @property ProductWriteoffPhoto[] $photos
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
            [['store_id', 'created_by', 'comment'], 'required'],
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
     * Gets query for [[Photos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPhotos()
    {
        return $this->hasMany(ProductWriteoffPhoto::class, ['writeoff_id' => 'id']);
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
                    // Если указано значение — используем его, иначе берём исходное количество
                    if (isset($approvedCounts[$item->id]) && $approvedCounts[$item->id] !== '' && $approvedCounts[$item->id] !== null) {
                        $item->approved_count = $approvedCounts[$item->id];
                    } else {
                        $item->approved_count = $item->count;
                    }
                    $item->save(false);
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

    /**
     * Отправить уведомление об утверждении списания в Telegram
     * @return bool
     */
    public function sendApprovalNotification()
    {
        $botToken = '2015516888:AAHcuE2OK2mVMKgnMCaI5M-jHfKybc_GY-Y';

        // Определяем chat_id на основе названия магазина
        $storeName = $this->store ? $this->store->name : '';

        if (stripos($storeName, 'Chopar') !== false) {
            $chatId = '-1001378351090';
        } elseif (stripos($storeName, 'Les') !== false) {
            $chatId = '-1001827735517';
        } else {
            // Если магазин не подходит под критерии — не отправляем
            Yii::warning("Writeoff #{$this->id}: store '{$storeName}' не содержит Chopar или Les, уведомление не отправлено", 'writeoff-telegram');
            return false;
        }

        // Формируем текст сообщения
        $message = "✅ *Списание #{$this->id} утверждено*\n\n";
        $message .= "📍 *Филиал:* {$storeName}\n";
        $message .= "📅 *Дата списания:* " . date('d.m.Y H:i', strtotime($this->created_at)) . "\n";
        $message .= "✔️ *Дата утверждения:* " . date('d.m.Y H:i', strtotime($this->approved_at)) . "\n";
        $message .= "👤 *Утвердил:* " . ($this->approvedBy ? $this->approvedBy->fullname : 'Неизвестно') . "\n";

        if ($this->comment) {
            $message .= "💬 *Комментарий:* {$this->comment}\n";
        }

        $message .= "\n📦 *Позиции:*\n";

        // Перезагружаем items чтобы получить актуальные approved_count
        $this->refresh();
        $items = ProductWriteoffItem::find()
            ->with('product')
            ->where(['writeoff_id' => $this->id])
            ->all();

        foreach ($items as $item) {
            $productName = $item->product ? $item->product->name : 'Неизвестный продукт';
            // Экранируем специальные символы Markdown
            $productName = str_replace(['*', '_', '`', '['], ['\\*', '\\_', '\\`', '\\['], $productName);
            $unit = $item->product ? $item->product->mainUnit : 'шт';
            $approvedCount = $item->approved_count ?? $item->count;
            $message .= "• {$productName}: *{$approvedCount}* {$unit}\n";
        }

        // Отправляем сообщение через Telegram API
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $params = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            Yii::info("Writeoff #{$this->id}: Telegram notification sent to {$chatId}", 'writeoff-telegram');
            return true;
        } else {
            Yii::error("Writeoff #{$this->id}: Failed to send Telegram notification. Response: {$response}", 'writeoff-telegram');
            return false;
        }
    }
}
