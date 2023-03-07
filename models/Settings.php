<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "settings".
 *
 * @property int $id
 * @property string $title
 * @property string $key
 * @property string $value
 * @property string $created
 * @property int $author_id
 *
 * @property User $author
 */
class Settings extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created'], 'safe'],
            [['author_id'], 'integer'],
            [['title', 'value'], 'string', 'max' => 255],
            [['key'], 'string', 'max' => 50],
            [['key'], 'unique'],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['author_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'key' => 'Ключ',
            'value' => 'Значение',
            'created' => 'Создано в',
            'author_id' => 'Автор',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'author_id']);
    }

    public static function getValue($key)
    {
        $model = self::findOne(['key' => $key]);
        if ($model == null)
            return null;
        return $model->value;
    }

    public static function setValue($key, $value)
    {
        $model = self::findOne(['key' => $key]);
        $model->value = $value;
        $model->save();
    }
}
