<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "oil_inventory_history".
 *
 * @property int $id
 * @property int $oil_inventory_id ID записи учета масла
 * @property int $user_id ID пользователя, внесшего изменение
 * @property string $field_name Название измененного поля
 * @property string|null $old_value Старое значение
 * @property string|null $new_value Новое значение
 * @property string $action Действие (create, update, delete)
 * @property string $created_at Дата и время изменения
 *
 * @property OilInventory $oilInventory
 * @property User $user
 */
class OilInventoryHistory extends \yii\db\ActiveRecord
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oil_inventory_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['oil_inventory_id', 'user_id', 'field_name', 'action'], 'required'],
            [['oil_inventory_id', 'user_id'], 'integer'],
            [['created_at'], 'safe'],
            [['field_name'], 'string', 'max' => 50],
            [['old_value', 'new_value'], 'string', 'max' => 255],
            [['action'], 'string', 'max' => 20],
            [['action'], 'in', 'range' => [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE]],
            [['oil_inventory_id'], 'exist', 'skipOnError' => true, 'targetClass' => OilInventory::class, 'targetAttribute' => ['oil_inventory_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'oil_inventory_id' => 'ID записи учета масла',
            'user_id' => 'Пользователь',
            'field_name' => 'Поле',
            'old_value' => 'Старое значение',
            'new_value' => 'Новое значение',
            'action' => 'Действие',
            'created_at' => 'Дата и время',
        ];
    }

    /**
     * Gets query for [[OilInventory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOilInventory()
    {
        return $this->hasOne(OilInventory::class, ['id' => 'oil_inventory_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Получить читаемое название поля
     * @param string $fieldName
     * @return string
     */
    public static function getFieldLabel($fieldName)
    {
        $labels = [
            'opening_balance' => 'Остаток на начало дня',
            'income' => 'Приход',
            'return_amount_kg' => 'Возврат (кг)',
            'return_amount' => 'Возврат (л)',
            'apparatus' => 'Аппарат',
            'new_oil' => 'Новое масло',
            'evaporation' => 'Испарение',
            'closing_balance' => 'Остаток на конец дня',
            'status' => 'Статус',
        ];

        return isset($labels[$fieldName]) ? $labels[$fieldName] : $fieldName;
    }

    /**
     * Получить читаемое название действия
     * @return string
     */
    public function getActionLabel()
    {
        $labels = [
            self::ACTION_CREATE => 'Создание',
            self::ACTION_UPDATE => 'Изменение',
            self::ACTION_DELETE => 'Удаление',
        ];

        return isset($labels[$this->action]) ? $labels[$this->action] : $this->action;
    }

    /**
     * Логирует изменение записи масла
     * @param int $oilInventoryId
     * @param string $fieldName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string $action
     * @return bool
     */
    public static function log($oilInventoryId, $fieldName, $oldValue, $newValue, $action = self::ACTION_UPDATE)
    {
        $history = new self();
        $history->oil_inventory_id = $oilInventoryId;
        $history->user_id = Yii::$app->user->id;
        $history->field_name = $fieldName;
        $history->old_value = $oldValue !== null ? (string)$oldValue : null;
        $history->new_value = $newValue !== null ? (string)$newValue : null;
        $history->action = $action;

        return $history->save();
    }
}
