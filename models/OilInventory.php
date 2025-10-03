<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "oil_inventory".
 *
 * @property int $id
 * @property string $store_id
 * @property double $opening_balance Остаток на начало дня
 * @property double $income Приход
 * @property double $return_amount Возврат (в литрах, автоматически рассчитывается)
 * @property double $return_amount_kg Возврат в килограммах (основное поле для ввода)
 * @property double $apparatus Аппарат
 * @property double $new_oil Новое масло
 * @property double $evaporation Испарение
 * @property double $closing_balance Остаток на конец дня
 * @property string $status Статус (новый, заполнен, отклонён, принят)
 * @property int $changes_count Количество изменений
 * @property int $created_by_user_id ID пользователя, создавшего запись
 * @property string $created_at
 * @property string $updated_at
 */
class OilInventory extends \yii\db\ActiveRecord
{
    const STATUS_NEW = 'новый';
    const STATUS_FILLED = 'заполнен';
    const STATUS_REJECTED = 'отклонён';
    const STATUS_ACCEPTED = 'принят';
    
    // Коэффициент конвертации кг в литры для масла (приблизительно)
    const KG_TO_LITERS_RATIO = 1.1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oil_inventory';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['store_id'], 'required'],
            [['opening_balance', 'income', 'return_amount', 'return_amount_kg', 'apparatus', 'new_oil', 'evaporation', 'closing_balance'], 'number'],
            [['opening_balance', 'return_amount_kg'], 'default', 'value' => 0],
            [['changes_count', 'created_by_user_id'], 'integer'],
            [['changes_count'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['store_id'], 'string', 'max' => 36],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_NEW, self::STATUS_FILLED, self::STATUS_REJECTED, self::STATUS_ACCEPTED]],
            [['status'], 'default', 'value' => self::STATUS_NEW],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'ID магазина',
            'opening_balance' => 'Остаток на начало дня (л)',
            'income' => 'Приход (л)',
            'return_amount' => 'Возврат (л)',
            'return_amount_kg' => 'Возврат (кг)',
            'apparatus' => 'Аппарат (л)',
            'new_oil' => 'Новое масло (л)',
            'evaporation' => 'Испарение (л)',
            'closing_balance' => 'Остаток на конец дня (л)',
            'status' => 'Статус',
            'changes_count' => 'Количество изменений',
            'created_by_user_id' => 'Создатель',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * Получить список всех возможных статусов
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_FILLED => 'Заполнен',
            self::STATUS_REJECTED => 'Отклонён',
            self::STATUS_ACCEPTED => 'Принят',
        ];
    }



    /**
     * Получить цветовую схему для статусов
     * @return array
     */
    public static function getStatusColors()
    {
        return [
            self::STATUS_NEW => 'info',
            self::STATUS_FILLED => 'warning',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_ACCEPTED => 'success',
        ];
    }

    /**
     * Получить название статуса
     * @return string
     */
    public function getStatusLabel()
    {
        $statuses = self::getStatusList();
        return isset($statuses[$this->status]) ? $statuses[$this->status] : $this->status;
    }

    /**
     * Получить цвет статуса
     * @return string
     */
    public function getStatusColor()
    {
        $colors = self::getStatusColors();
        return isset($colors[$this->status]) ? $colors[$this->status] : 'default';
    }

    /**
     * Конвертировать килограммы в литры
     * @param float $kg
     * @return float
     */
    public static function convertKgToLiters($kg)
    {
        return $kg * self::KG_TO_LITERS_RATIO;
    }

    /**
     * Конвертировать литры в килограммы
     * @param float $liters
     * @return float
     */
    public static function convertLitersToKg($liters)
    {
        return $liters / self::KG_TO_LITERS_RATIO;
    }

    /**
     * Получить возврат в литрах (автоматическая конвертация из кг)
     * @return float
     */
    public function getReturnInLiters()
    {
        return self::convertKgToLiters($this->return_amount_kg);
    }

    /**
     * Автоматический расчет испарения
     * @return float
     */
    public function calculateEvaporation()
    {
        $returnInLiters = $this->getReturnInLiters();
        return $this->opening_balance + $this->income - $returnInLiters - $this->apparatus - $this->new_oil;
    }

    /**
     * Автоматический расчет остатка на конец дня
     * @return float
     */
    public function calculateClosingBalance()
    {
        return $this->apparatus + $this->new_oil;
    }

    /**
     * Обновить испарение и остаток на конец дня автоматически
     */
    public function updateCalculatedFields()
    {
        // Автоматически конвертируем кг в литры для внутренних расчётов
        $this->return_amount = self::convertKgToLiters($this->return_amount_kg);
        
        $this->evaporation = $this->calculateEvaporation();
        $this->closing_balance = $this->calculateClosingBalance();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Автоматически обновляем испарение и остаток на конец дня перед сохранением
            $this->updateCalculatedFields();
            return true;
        }
        return false;
    }

    /**
     * Связь с магазином
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Stores::class, ['id' => 'store_id']);
    }

    /**
     * Связь с историей изменений
     * @return \yii\db\ActiveQuery
     */
    public function getHistory()
    {
        return $this->hasMany(OilInventoryHistory::class, ['oil_inventory_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Обновить счетчик изменений на основе записей в истории
     * @return bool
     */
    public function updateChangesCount()
    {
        $count = OilInventoryHistory::find()
            ->where(['oil_inventory_id' => $this->id])
            ->andWhere(['action' => OilInventoryHistory::ACTION_UPDATE])
            ->count();

        $this->changes_count = $count;

        return $this->updateAttributes(['changes_count' => $this->changes_count]);
    }

    /**
     * Увеличить счетчик изменений на 1
     * @return bool
     */
    public function incrementChangesCount()
    {
        $this->changes_count = (int)$this->changes_count + 1;
        return $this->updateAttributes(['changes_count' => $this->changes_count]);
    }

    /**
     * Связь с пользователем-создателем записи
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by_user_id']);
    }

    /**
     * Проверяет, может ли текущий пользователь редактировать запись
     * Ограничения:
     * - Не более 2 раз редактирования
     * - Только в течение 2 дней с даты создания
     * - Только создатель записи может редактировать
     *
     * @param int|null $userId ID пользователя (если не указан, берется текущий)
     * @return bool
     */
    public function canEdit($userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        // Проверяем, является ли пользователь создателем записи
        if ($this->created_by_user_id != $userId) {
            return false;
        }

        // Проверяем количество изменений (не более 2 раз)
        if ($this->changes_count >= 10) {
            return false;
        }

        // Проверяем срок давности (не более 2 дней с момента создания)
        $createdDate = new \DateTime($this->created_at);
        $now = new \DateTime();
        $daysDiff = $createdDate->diff($now)->days;

        if ($daysDiff > 2) {
            return false;
        }

        return true;
    }

    /**
     * Получает причину, по которой запись нельзя редактировать
     * @param int|null $userId ID пользователя (если не указан, берется текущий)
     * @return string|null Сообщение об ошибке или null, если можно редактировать
     */
    public function getEditRestrictionReason($userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        // Проверяем, является ли пользователь создателем записи
        if ($this->created_by_user_id != $userId) {
            return 'Вы не можете редактировать эту запись, так как она была создана другим пользователем.';
        }

        // Проверяем количество изменений
        if ($this->changes_count >= 2) {
            return 'Вы достигли максимального количества редактирований (2 раза).';
        }

        // Проверяем срок давности
        $createdDate = new \DateTime($this->created_at);
        $now = new \DateTime();
        $daysDiff = $createdDate->diff($now)->days;

        if ($daysDiff > 2) {
            return 'Срок редактирования истёк (можно редактировать только в течение 2 дней с момента создания).';
        }

        return null;
    }

    /**
     * Получает информацию о доступных редактированиях
     * @return array
     */
    public function getEditInfo()
    {
        $createdDate = new \DateTime($this->created_at);
        $now = new \DateTime();
        $daysDiff = $createdDate->diff($now)->days;
        $daysLeft = max(0, 2 - $daysDiff);
        $editsLeft = max(0, 2 - $this->changes_count);

        return [
            'edits_made' => $this->changes_count,
            'edits_left' => $editsLeft,
            'days_passed' => $daysDiff,
            'days_left' => $daysLeft,
            'can_edit' => $this->canEdit(),
        ];
    }
}
