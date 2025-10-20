<?php

namespace app\models;

use Yii;
use yii\db\mssql\PDO;

/**
 * This is the model class for table "order_items".
 *
 * @property int $orderId
 * @property string $productId
 * @property string $quantity
 * @property string $available
 * @property string $storeId
 * @property string $storeSupplierId
 * @property string $storeQuantity
 * @property string $factStoreQuantity
 * @property string $supplierId
 * @property string $supplierQuantity
 * @property string $purchaseQuantity
 * @property string $price
 * @property string $supplierDescription
 * @property string $factSupplierQuantity
 * @property string $factOfficeQuantity
 * @property int $userId
 * @property int $shipped_from_warehouse
 * @property int $returned_quantity
 * @property int $prepared
 * @property int $minused
 * @property string $deleted_at
 * @property int $deleted_by
 *
 * @property Products $product
 * @property User $deletedBy
 */
class OrderItems extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orderId', 'productId', 'quantity'], 'required'],
            [['orderId', 'userId', 'deleted_by'], 'integer'],
            [['quantity', 'storeQuantity', 'factStoreQuantity', 'supplierQuantity', 'purchaseQuantity', 'price', 'factSupplierQuantity', 'available', 'shipped_from_warehouse', 'factOfficeQuantity', 'returned_quantity', 'prepared', 'minused'], 'number'],
            [['productId', 'storeId', 'supplierId', 'storeSupplierId'], 'string', 'max' => 36],
            [['supplierDescription'], 'string'],
            [['deleted_at'], 'safe'],
            [['orderId', 'productId'], 'unique', 'targetAttribute' => ['orderId', 'productId']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'orderId' => 'Заказ',
            'productId' => 'Продукт',
            'quantity' => 'Кол. заказа',
            'available' => 'В наличии',
            'storeId' => 'Default Store ID',
            'storeSupplierId' => 'Store Supplier ID',
            'storeQuantity' => 'Кол. склад',
            'factStoreQuantity' => 'Факт приёма',
            'supplierId' => 'Supplier ID',
            'supplierQuantity' => 'Кол. закуп.',
            'factSupplierQuantity' => 'Факт закуп.',
            'userId' => 'User ID',
            'shipped_from_warehouse' => 'Отправлен со склада',
            'factOfficeQuantity' => 'Факт офис',
            'returned_quantity' => 'Возврат',
            'prepared' => 'Подготовлено',
            'minused' => 'Обнулён',
            'deleted_at' => 'Дата удаления',
            'deleted_by' => 'Удалил'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::className(), ['id' => 'productId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeletedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'deleted_by']);
    }

    public static function getStockOrderByProducts()
    {
        $stockId = User::getStoreId();
        $data = date("Y-m-d");

        $in = "";
        $products = Products::getUserProducts();
        foreach ($products as $p) {
            $in .= "'{$p}',";
        }
        if (!empty($in)) {
            $in = substr($in, 0, -1);
            $in = " and oi.productId in({$in})";
        }

        $sql = "select oi.orderId,oi.productId,p.name,p.mainUnit,p.inStock,sum(oi.quantity) as total from orders o
                left join order_items oi on oi.orderId=o.id
                left join products p on p.id=oi.productId
                where o.state=0 and o.storeId!=:s {$in} and p.id is not null and oi.deleted_at is null
                group by oi.productId order by p.name asc";
        return Yii::$app->db->createCommand($sql)
//            ->bindValue(":d", $data, PDO::PARAM_STR)
            ->bindValue(":s", $stockId, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function getBuyerOrderByProducts()
    {
        $data = date("Y-m-d");
        $in = "";
        $products = Products::getUserProducts();
        foreach ($products as $p) {
            $in .= "'{$p}',";
        }
        if (!empty($in)) {
            $in = substr($in, 0, -1);
            $in = " and oi.productId in({$in})";
        }
        $sql = "select oi.orderId,oi.productId,p.name,p.mainUnit,sum(oi.supplierQuantity) as total from orders o
                left join order_items oi on oi.orderId=o.id
                left join products p on p.id=oi.productId
                where o.state=0 and oi.supplierQuantity>0 {$in} and oi.deleted_at is null
                group by oi.productId order by p.name asc";
        return Yii::$app->db->createCommand($sql)
//            ->bindValue(":d", $data, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function getStockItemsList($id)
    {
        $sql = "select oi.*,p.name,p.mainUnit from order_items oi
                left join products p on p.id=oi.productId
                where oi.orderId=:id and oi.storeQuantity>0 and oi.deleted_at is null";
        return Yii::$app->db->createCommand($sql)
            ->bindValue(":id", $id, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function getSupplierItemsList($id)
    {
        $sql = "select oi.*,p.name,p.mainUnit from order_items oi
                left join products p on p.id=oi.productId
                where oi.orderId=:id and oi.supplierQuantity>0 and oi.deleted_at is null";
        return Yii::$app->db->createCommand($sql)
            ->bindValue(":id", $id, PDO::PARAM_STR)
            ->queryAll();
    }

    /**
     * Переопределяем метод find() для автоматического исключения удаленных записей
     *
     * @return \yii\db\ActiveQuery
     */
    public static function find()
    {
        return parent::find()->where(['deleted_at' => null]);
    }

    /**
     * Возвращает query для получения всех записей, включая удаленные
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findWithDeleted()
    {
        return parent::find();
    }

    /**
     * Возвращает query для получения только удаленных записей
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findOnlyDeleted()
    {
        return parent::find()->where(['not', ['deleted_at' => null]]);
    }

    /**
     * Soft delete - помечает запись как удаленную
     *
     * @return false|int
     */
    public function delete()
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        $this->deleted_by = Yii::$app->user->id;
        return $this->save(false);
    }

    /**
     * Физическое удаление записи из базы данных
     *
     * @return false|int
     */
    public function forceDelete()
    {
        return parent::delete();
    }

    /**
     * Восстановление удаленной записи
     *
     * @return bool
     */
    public function restore()
    {
        $this->deleted_at = null;
        $this->deleted_by = null;
        return $this->save(false);
    }

    /**
     * Проверяет, удалена ли запись
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted_at !== null;
    }
}
