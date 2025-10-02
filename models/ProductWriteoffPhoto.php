<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_writeoff_photos".
 *
 * @property int $id
 * @property int $writeoff_id ID списания
 * @property string $filename Имя файла
 * @property string $uploaded_at Дата загрузки
 *
 * @property ProductWriteoff $writeoff
 */
class ProductWriteoffPhoto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_writeoff_photos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['writeoff_id', 'filename'], 'required'],
            [['writeoff_id'], 'integer'],
            [['uploaded_at'], 'safe'],
            [['filename'], 'string', 'max' => 255],
            [['writeoff_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductWriteoff::class, 'targetAttribute' => ['writeoff_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'writeoff_id' => 'ID списания',
            'filename' => 'Файл',
            'uploaded_at' => 'Дата загрузки',
        ];
    }

    /**
     * Gets query for [[Writeoff]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWriteoff()
    {
        return $this->hasOne(ProductWriteoff::class, ['id' => 'writeoff_id']);
    }

    /**
     * Получить полный путь к файлу
     *
     * @return string
     */
    public function getFilePath()
    {
        return Yii::getAlias('@webroot/uploads/writeoff-photos/' . $this->filename);
    }

    /**
     * Получить URL файла
     *
     * @return string
     */
    public function getFileUrl()
    {
        return Yii::getAlias('@web/uploads/writeoff-photos/' . $this->filename);
    }

    /**
     * Удалить файл при удалении записи
     *
     * @return bool
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            $filePath = $this->getFilePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return true;
        }
        return false;
    }
}
