<?php

namespace app\models;

use Yii;
use yii\db\mssql\PDO;
use yii\db\Query;

/**
 * This is the model class for table "orders".
 *
 * @property int $id
 * @property int $userId
 * @property string $storeId ID склад филиала
 * @property string $defaultStoreId
 * @property string $supplierId ID поставщика
 * @property string $outgoingDocumentId ID документа со склада к филиалу
 * @property string $incomingDocumentId ID документа приход к филиалу
 * @property string $supplierDocumentId ID документа со поставщика
 * @property string $date Дата
 * @property string $comment
 * @property string $addDate
 * @property int $state
 * @property int $editable
 * @property string $sent_date
 * @property bool $is_market
 * @property bool $is_locked
 * @property string $deleted_at
 * @property string $deleted_by
 *
 * @property Stores $store
 * @property User $user
 * @property OrderItems[] $items
 */
class Orders extends \yii\db\ActiveRecord
{
    public static $states = [
        0 => "Новый",
        1 => "Отправлен",
        2 => "Завершен",
        3 => "Проверка офисом",
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orders';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userId', 'date'], 'required'],
            [['userId', 'state', 'editable'], 'integer'],
            [['date', 'addDate', 'sent_date'], 'safe'],
            [['comment'], 'string'],
            [['office_comment'], 'string'],
            [['defaultStoreId', 'storeId', 'supplierId', 'outgoingDocumentId', 'incomingDocumentId', 'supplierDocumentId', 'deleted_at', 'deleted_by'], 'string', 'max' => 36],
            [['userId'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userId' => 'id']],
            [['is_market'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Номер заказа',
            'userId' => 'Заказчик',
            'storeId' => 'Склад',
            'defaultStoreId' => 'Default Store ID',
            'supplierId' => 'Supplier ID',
            'date' => 'Дата',
            'comment' => 'Комментарий',
            'addDate' => 'Добавлен в',
            'state' => 'Статус',
            'editable' => 'Editable',
            'office_comment' => 'Комментарий офиса',
            'sent_date' => 'Отправлено в',
            'is_market' => 'Базар',
            'deleted_at' => 'Удалено в',
            'deleted_by' => 'Удалено пользователем',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeletedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'deleted_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(OrderItems::className(), ['orderId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Stores::className(), ['id' => 'storeId']);
    }

    public static function getOrders($date, $all = false)
    {
        $query = new Query();
        $cmd = $query->select("stores.id,stores.name,orders.id,orders.date,orders.storeId,orders.comment,orders.addDate,orders.state")
            ->from("orders")
            ->leftJoin("stores", "stores.id=orders.storeId")
//            ->where("orders.date=:d", [":d" => $date])
            ->orderBy("stores.name");
        if (!$all) {
            $cmd->andWhere("orders.state<2");
        }
        return $cmd->all();
    }

    public static function getOrderStockProducts()
    {
        $r = [];
        $in = "";
        $products = Products::getUserProducts();
        foreach ($products as $p) {
            $in .= "'{$p}',";
        }
        if (!empty($in)) {
            $in = substr($in, 0, -1);
            $in = "and oi.productId in({$in})";
        }
        $sql = "select o.id,oi.productId,oi.quantity,p.parentId,p.name, p1.name as parent from orders o
                left join order_items oi on oi.orderId=o.id
                left join products p on p.id=oi.productId
				left join products p1 on p1.id=p.parentId
                where o.state=0 {$in}
                group by oi.productId, o.id
				order by p.parentId";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        foreach ($data as $row) {
            if (empty($row['name']))
                continue;
            $r[$row['productId']]['name'] = $row['name'];
            $r[$row['productId']]['parentId'] = $row['parentId'];
            $r[$row['productId']]['parentName'] = $row['parent'];
            $r[$row['productId']][$row['id']] = $row['quantity'];
        }
        return $r;
    }

    public static function getOrderSupplierProducts()
    {
        $r = [];
        $in = "";
        $products = Products::getUserProducts();
        foreach ($products as $p) {
            $in .= "'{$p}',";
        }
        if (!empty($in)) {
            $in = substr($in, 0, -1);
            $in = "and oi.productId in({$in})";
        }
        $sql = "select o.id,oi.productId,oi.supplierQuantity,p.parentId,p.name, p1.name as parent from orders o
                left join order_items oi on oi.orderId=o.id
                left join products p on p.id=oi.productId
				left join products p1 on p1.id=p.parentId
                where o.state=0 {$in} and oi.supplierQuantity>0
                group by oi.productId, o.id
				order by p.parentId";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        foreach ($data as $row) {
            if (empty($row['name']))
                continue;
            $r[$row['productId']]['name'] = $row['name'];
            $r[$row['productId']]['parentId'] = $row['parentId'];
            $r[$row['productId']]['parentName'] = $row['parent'];
            $r[$row['productId']][$row['id']] = $row['supplierQuantity'];
        }
        return $r;
    }

    public static function getOrderProducts($id, $is_market = false)
    {
        $query = new Query();
        if (!empty(Yii::$app->user->identity->product_group_id)) {
            return $query->select("products.id,products.parentId,pg.name as groupName,products.name,products.mainUnit,order_items.*,products.price,order_items.prepared,order_items.minused")
                ->from("order_items")
                ->leftJoin("products", "products.id=order_items.productId")
                ->leftJoin("product_groups_link pgl", "pgl.productId=products.id")
                ->leftJoin("product_groups pg", "pg.id=pgl.productGroupId")
                ->leftJoin("products p1", "p1.id=products.parentId")
                ->where("order_items.orderId=:id and order_items.productId in(select category_id from user_categories where user_id=:u) and pgl.productGroupId = :d and (pg.is_market = :m or pg.is_market is null)", [":id" => $id, ':u' => Yii::$app->user->id, ':d' => Yii::$app->user->identity->product_group_id, ':m' => $is_market ? 1 : 0])
                ->groupBy("order_items.productId")
//            ->orderBy("p1.name,products.name")
                ->all();

        } else {
            return $query->select("products.id,products.parentId,pg.name as groupName,products.name,products.mainUnit,order_items.*,products.price")
                ->from("order_items")
                ->leftJoin("products", "products.id=order_items.productId")
                ->leftJoin("product_groups_link pgl", "pgl.productId=products.id")
                ->leftJoin("product_groups pg", "pg.id=pgl.productGroupId")
                ->leftJoin("products p1", "p1.id=products.parentId")
                ->where("order_items.orderId=:id and order_items.productId in(select category_id from user_categories where user_id=:u) and (pg.is_market = :m or pg.is_market is null)", [":id" => $id, ':u' => Yii::$app->user->id, ':m' => $is_market ? 1 : 0])
                ->groupBy("order_items.productId")
//            ->orderBy("p1.name,products.name")
                ->all();
        }
    }

    public static function getOrderSupplier($id)
    {
        $query = new Query();
        return $query->select("products.id,products.parentId,p1.name as groupName,products.name,products.mainUnit,order_items.*")
            ->from("order_items")
            ->leftJoin("products", "products.id=order_items.productId")
            ->leftJoin("products p1", "p1.id=products.parentId")
            ->where("order_items.orderId=:id and order_items.supplierQuantity>0 and order_items.productId in(select category_id from user_categories where user_id=:u)", [":id" => $id, ':u' => Yii::$app->user->id])
//            ->orderBy("p1.name,products.name")
            ->all();
    }

    public static function getCustomer($id)
    {
        $model = self::findOne($id);
        if ($model == null)
            return "-";
        return $model->store->name;
    }

    public static function getStoreMan($id)
    {
        $model = self::findOne($id);
        if ($model == null)
            return "-";
        $sql = "select s.name from order_items oi
                left join stores s on s.id=oi.storeId
                where oi.orderId=:id and storeId is not null
                limit 1";
        return Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $id, PDO::PARAM_INT)
            ->queryScalar();
    }

    public static function getSupplier($id)
    {
        $model = self::findOne($id);
        if ($model == null)
            return "-";
        $sql = "select s.name from order_items oi
                left join suppliers s on s.id=oi.supplierId
                where oi.orderId=:id and storeId is not null
                limit 1";
        return Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $id, PDO::PARAM_INT)
            ->queryScalar();
    }

    public function canClose()
    {
        $sql1 = "select count(*) from order_items where orderId=:o and storeQuantity>0";
        $store = Yii::$app->db->createCommand($sql1)->bindValue(":o", $this->id, PDO::PARAM_INT)->queryScalar();

        $sql3 = "select count(*) from order_items where orderId=:o and purchaseQuantity>0";
        $supplier = Yii::$app->db->createCommand($sql3)->bindValue(":o", $this->id, PDO::PARAM_INT)->queryScalar();

        if ($store > 0 || $supplier > 0) {
            return true;
        }
        return false;
    }
}

