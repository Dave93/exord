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
} 
