<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_categories".
 *
 * @property int $user_id
 * @property string $category_id
 *
 * @property Products $category
 * @property User $user
 */
class UserCategories extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_categories';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'category_id'], 'required'],
            [['user_id'], 'integer'],
            [['category_id'], 'string', 'max' => 36],
            [['user_id', 'category_id'], 'unique', 'targetAttribute' => ['user_id', 'category_id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Products::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'category_id' => 'Category ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Products::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public static function isLast($id)
    {
        $sql = "select count(*) from products where parentId=:id and productType=''";
        $c = Yii::$app->db->createCommand($sql)
            ->bindValue(":id", $id, \PDO::PARAM_STR)
            ->queryScalar();
        return $c == 0;
    }
}
