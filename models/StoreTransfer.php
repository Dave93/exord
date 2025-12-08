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

    /**
     * Отправить уведомление о подтверждении передачи в Telegram
     * @param string $sourceStoreId ID филиала-источника, который подтвердил передачу
     * @return bool
     */
    public function sendTransferConfirmationNotification($sourceStoreId)
    {
        $debugFile = __DIR__ . '/transfer_notification_debug.txt';
        @file_put_contents($debugFile, "=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        @file_put_contents($debugFile, "Transfer ID: {$this->id}, SourceStoreId: {$sourceStoreId}\n", FILE_APPEND);

        $botToken = '2015516888:AAHcuE2OK2mVMKgnMCaI5M-jHfKybc_GY-Y';

        // Определяем chat_id на основе названия магазина-получателя
        $requestStoreName = $this->requestStore ? $this->requestStore->name : '';
        @file_put_contents($debugFile, "RequestStoreName: {$requestStoreName}\n", FILE_APPEND);

        if (stripos($requestStoreName, 'Chopar') !== false) {
            $chatId = '-1001378351090';
        } elseif (stripos($requestStoreName, 'Les') !== false) {
            $chatId = '-1001827735517';
        } else {
            Yii::warning("Transfer #{$this->id}: requestStore '{$requestStoreName}' не содержит Chopar или Les, уведомление не отправлено", 'transfer-telegram');
            return false;
        }

        // Получаем филиал-источник
        $sourceStore = Stores::findOne($sourceStoreId);
        $sourceStoreName = $sourceStore ? $sourceStore->name : 'Неизвестный филиал';

        // Формируем текст сообщения
        $message = "📦 *Перемещение #{$this->id}*\n\n";
        $message .= "🏪 *Откуда:* {$sourceStoreName}\n";
        $message .= "🏪 *Куда:* {$requestStoreName}\n";
        $message .= "📅 *Дата заявки:* " . date('d.m.Y H:i', strtotime($this->created_at)) . "\n";
        $message .= "✔️ *Дата подтверждения:* " . date('d.m.Y H:i') . "\n";

        if ($this->comment) {
            $message .= "💬 *Комментарий:* {$this->comment}\n";
        }

        $message .= "\n📋 *Передаваемые позиции:*\n";

        // Получаем позиции только для указанного филиала-источника
        $items = StoreTransferItem::find()
            ->with('product')
            ->where([
                'transfer_id' => $this->id,
                'source_store_id' => $sourceStoreId,
                'item_status' => StoreTransferItem::STATUS_TRANSFERRED,
            ])
            ->all();

        @file_put_contents($debugFile, "Items count: " . count($items) . "\n", FILE_APPEND);

        if (empty($items)) {
            @file_put_contents($debugFile, "ERROR: No items with TRANSFERRED status\n\n", FILE_APPEND);
            Yii::warning("Transfer #{$this->id}: нет переданных позиций для филиала {$sourceStoreId}", 'transfer-telegram');
            return false;
        }

        foreach ($items as $item) {
            $productName = $item->product ? $item->product->name : 'Неизвестный продукт';
            // Экранируем специальные символы Markdown
            $productName = str_replace(['*', '_', '`', '['], ['\\*', '\\_', '\\`', '\\['], $productName);
            $unit = $item->product ? $item->product->mainUnit : 'шт';
            $transferredQty = $item->transferred_quantity ?? 0;
            $message .= "• {$productName}: *{$transferredQty}* {$unit}\n";
        }

        @file_put_contents($debugFile, "Message: {$message}\n", FILE_APPEND);
        @file_put_contents($debugFile, "ChatId: {$chatId}\n", FILE_APPEND);

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

        @file_put_contents($debugFile, "HTTP Code: {$httpCode}\n", FILE_APPEND);
        @file_put_contents($debugFile, "Response: {$response}\n\n", FILE_APPEND);

        if ($httpCode === 200) {
            Yii::info("Transfer #{$this->id}: Telegram notification sent to {$chatId}", 'transfer-telegram');
            return true;
        } else {
            Yii::error("Transfer #{$this->id}: Failed to send Telegram notification. Response: {$response}", 'transfer-telegram');
            return false;
        }
    }
}
