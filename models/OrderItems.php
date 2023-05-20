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
 *
 * @property Products $product
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
            [['orderId', 'userId'], 'integer'],
            [['quantity', 'storeQuantity', 'factStoreQuantity', 'supplierQuantity', 'purchaseQuantity', 'price', 'factSupplierQuantity', 'available', 'shipped_from_warehouse', 'factOfficeQuantity'], 'number'],
            [['productId', 'storeId', 'supplierId', 'storeSupplierId'], 'string', 'max' => 36],
            [['supplierDescription'], 'string'],
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::className(), ['id' => 'productId']);
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
                where o.state=0 and o.storeId!=:s {$in} and p.id is not null
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
                where o.state=0 and oi.supplierQuantity>0 {$in}
                group by oi.productId order by p.name asc";
        return Yii::$app->db->createCommand($sql)
//            ->bindValue(":d", $data, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function getStockItemsList($id)
    {
        $sql = "select oi.*,p.name,p.mainUnit from order_items oi
                left join products p on p.id=oi.productId 
                where oi.orderId=:id and oi.storeQuantity>0";
        return Yii::$app->db->createCommand($sql)
            ->bindValue(":id", $id, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function getSupplierItemsList($id)
    {
        $sql = "select oi.*,p.name,p.mainUnit from order_items oi
                left join products p on p.id=oi.productId 
                where oi.orderId=:id and oi.supplierQuantity>0";
        return Yii::$app->db->createCommand($sql)
            ->bindValue(":id", $id, PDO::PARAM_STR)
            ->queryAll();
    }
}
