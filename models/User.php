<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;


/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property integer $state
 * @property integer $role
 * @property string $store_id
 * @property string $supplier_id
 * @property string $username
 * @property string $password
 * @property string $fullname
 * @property string $phone
 * @property string $email
 * @property int $percentage
 * @property string $description
 * @property string $authKey
 * @property string $accessToken
 * @property string $regDate
 * @property string $lastVisit
 * @property int $showPrice
 * @property int $terminalId
 * @property int $product_group_id
 * @property string $oil_tg_id
 *
 * @property Stores $store
 * @property Suppliers $supplier
 * @property Products[] $categories
 *
 */
class User extends ActiveRecord implements IdentityInterface
{
    public $category;
    public $newPassword;

    const ROLE_ADMIN = 1;
    const ROLE_MANAGER = 2;
    const ROLE_STOCK = 3;
    const ROLE_BUYER = 4;
    const ROLE_BARMEN = 5;
    const ROLE_COOK = 6;
    const ROLE_PASTRY = 7;
    const ROLE_ETAJ = 9;

    const ROLE_OFFICE = 8;
    const ROLE_OFFICE_MANAGER = 10;

    public static $roles = [
        1 => 'Администратор',
        2 => 'Менеджер',
        3 => 'Складщик',
        4 => 'Закупщик',
        5 => 'Бариста',
        6 => 'Повар',
        7 => 'Кондитер',
        8 => 'Офис',
        9 => 'Этаж склада',
        10 => 'Офис менеджер'
    ];

    public static $states = [
        0 => 'Блок',
        1 => 'Актив'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['role', 'username', 'password', 'fullname', 'phone'], 'required', 'on' => 'create'],
            [['role', 'username', 'fullname', 'phone'], 'required', 'on' => 'update'],
            [['state', 'role', 'percentage', 'showPrice', 'product_group_id'], 'integer'],
            [['regDate', 'lastVisit'], 'safe'],
            [['username', 'email'], 'string', 'max' => 30],
            [['password'], 'string', 'max' => 150],
            [['fullname', 'phone'], 'string', 'max' => 50],
            [['store_id', 'supplier_id'], 'string', 'max' => 36],
            [['newPassword'], 'string', 'max' => 30],
            [['description'], 'string'],
            [['category'], 'safe'],
            [['authKey', 'accessToken'], 'string', 'max' => 255],
            [['username'], 'unique'],
            [['terminalId'], 'string'],
            [['oil_tg_id'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'state' => 'Статус',
            'role' => 'Роль',
            'store_id' => 'Склад',
            'supplier_id' => 'Поставщик',
            'username' => 'Пользователь',
            'password' => 'Пароль',
            'newPassword' => 'Пароль',
            'fullname' => 'ФИО',
            'address' => 'Адрес',
            'phone' => 'Телефон',
            'email' => 'E-mail',
            'percentage' => 'Наценка',
            'description' => 'Описание',
            'authKey' => 'Код авторизации',
            'accessToken' => 'Токен',
            'regDate' => 'Дата регистрации',
            'lastVisit' => 'Последний визит',
            'category' => 'Категории',
            'showPrice' => 'Показать сумму',
            'terminalId' => 'Ид филиала',
            'product_group_id' => 'Этаж товаров',
            'oil_tg_id' => 'Ид в ТГ'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Stores::className(), ['id' => 'store_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupplier()
    {
        return $this->hasOne(Suppliers::className(), ['id' => 'supplier_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Products::className(), ['id' => 'category_id'])->viaTable('user_categories', ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductGroup()
    {
        return $this->hasOne(ProductGroups::className(), ['id' => 'product_group_id']);
    }

    /**
     * Finds an identity by the given ID.
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        // TODO: Implement findIdentity() method.
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // TODO: Implement findIdentityByAccessToken() method.
        return static::findOne(['accessToken' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'state' => 1]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function getIsAdmin()
    {
        return $this->role == self::ROLE_ADMIN;
    }

    public function getIsManager()
    {
        return $this->role == self::ROLE_MANAGER;
    }

    public function getIsStorage()
    {
        return $this->role == self::ROLE_STOCK;
    }

    public function getIsBuyer()
    {
        return $this->role == self::ROLE_BUYER;
    }

    public function getIsBarmen()
    {
        return $this->role == self::ROLE_BARMEN;
    }

    public function getIsCook()
    {
        return $this->role == self::ROLE_COOK;
    }

    public function getIsPastry()
    {
        return $this->role == self::ROLE_PASTRY;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    public function generatePassword($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        return Yii::$app->security->generateRandomString();
    }

    public function generateAccessToken()
    {
        return Yii::$app->security->generateRandomString();
    }

    public static function getList()
    {
        return ArrayHelper::map(self::find()->orderBy(['fullname' => SORT_ASC])->all(), 'id', 'fullname');
    }

    public static function getEstablishmentId()
    {
        $model = self::findOne(Yii::$app->user->id);
        return $model->store_id;
    }

    public static function getUserCategories($id)
    {
        $query = new Query();
        return $query->select("products.id,products.name")
            ->from("user_categories")
            ->leftJoin("products", "products.id=user_categories.category_id")
            ->where("user_categories.user_id=:id", [":id" => $id])
            ->orderBy("products.name")
            ->all();
    }

    public static function getFullName()
    {
        $model = self::findOne(Yii::$app->user->id);
        return $model->fullname;
    }

    public static function getStoreId()
    {
        $model = self::findOne(Yii::$app->user->id);
        return $model->store_id;
    }

    public static function getSupplierId()
    {
        $model = self::findOne(Yii::$app->user->id);
        return $model->supplier_id;
    }


    /**
     * Returns user role name according to RBAC
     * @return string
     */
    public function getRoleName()
    {
        $roles = Yii::$app->authManager->getRolesByUser($this->id);
        if (!$roles) {
            return null;
        }

        reset($roles);
        /* @var $role \yii\rbac\Role */
        $role = current($roles);

        return $role->name;
    }

    public static function getUsers() {
        $query = new Query();
        $data = $query->select("id,username")
            ->from("user")
            ->where("state=1")
            ->orderBy("username")
            ->all();
        return ArrayHelper::map($data, 'id', 'username');
    }
}
