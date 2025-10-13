<?php

namespace app\models;

use Yii;
use yii\db\mssql\PDO;
use yii\db\Query;

/**
 * This is the model class for table "products".
 *
 * @property string $id
 * @property string $parentId
 * @property int $code
 * @property string $num
 * @property string $name
 * @property string $zone
 * @property string $mainUnit
 * @property string $cookingPlaceType
 * @property string $price
 * @property string $price_start
 * @property string $price_end
 * @property string $alternative_price
 * @property string $alternative_date
 * @property string $productType
 * @property string $syncDate
 * @property string $delta
 * @property string $description
 * @property double $inStock
 * @property double $minBalance
 * @property int $showOnReport
 */
class Products extends \yii\db\ActiveRecord
{
    public static $types = [
        "GOODS" => "Товар",
        "DISH" => "Блюдо",
        "PREPARED" => "Заготовка",
        "SERVICE" => "Услуга",
        "MODIFIER" => "Модификатор",
        "OUTER" => "Внешние товары",
        "PETROL" => "Топливо",
        "RATE" => "Тариф",
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'products';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['code', 'showOnReport'], 'integer'],
            [['price_start', 'price_end', 'delta', 'inStock', 'minBalance'], 'number'],
            [['syncDate','price'], 'safe'],
            [['id', 'parentId'], 'string', 'max' => 36],
            [['cookingPlaceType', 'productType', 'num'], 'string', 'max' => 50],
            [['mainUnit'], 'string', 'max' => 20],
            [['name', 'zone'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parentId' => 'Родительская группа',
            'code' => 'Код',
            'num' => 'Номер',
            'name' => 'Название',
            'zone' => 'Зона',
            'mainUnit' => 'Ед. изм',
            'cookingPlaceType' => 'Тип место приготовления',
            'price' => 'Цена',
            'price_start' => 'Цена от',
            'price_end' => 'Цена до',
            'alternative_price' => 'Альтернативная цена',
            'alternative_date' => 'Дата',
            'productType' => 'Тип продукта',
            'syncDate' => 'Дата синхронизации',
            'delta' => 'Дельта',
            'showOnReport' => 'Показать в отчетах',
            'description' => 'Описание',
            'minBalance' => 'Минимальный остаток',
            'inStock' => 'In Stock',
        ];
    }

    public static function hasSubGroup($id)
    {
        $sql = "select count(*) from products where parentId=:id";
        $c = Yii::$app->db->createCommand($sql)
            ->bindValue(":id", $id, PDO::PARAM_STR)
            ->queryScalar();
        return $c > 0;
    }

    public static function getTree($id = 0)
    {
        if (empty($id))
            $sql = "select * from (select p.id,p.name,p.productType,(select count(*) from products where parentId=p.id) as count from products p where parentId=:id order by name asc) q1 where q1.count>0 order by name";
        else
            $sql = "select name,id,productType from products where parentId=:id order by name asc";
        $data = Yii::$app->db->createCommand($sql)->bindParam(":id", $id, PDO::PARAM_STR)->queryAll();
        $li = "";
        if (empty($data))
            return "";
        foreach ($data as $row) {
            $ul = self::getTree($row['id']);
            $attr = '';
            if (!empty($row['productType'])) {
                $attr = "data-action='edit-product' data-id='{$row['id']}'";
            }
            $li .= "<li><a href='#' {$attr}>{$row['name']}</a>{$ul}</li>";
        }
        return "<ul class=\"category-tree\">{$li}</ul>";
    }

    public static function getHierarchy($id = 0, $selected = null)
    {
        if (empty($id))
            $sql = "select * from (select p.id,p.name,(select count(*) from products where parentId=p.id) as count from products p where parentId=:id order by name asc) q1 where q1.count>0 order by name";
        else
            $sql = "select name,id from products where parentId=:id order by name asc";
        $data = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $id, PDO::PARAM_STR)
            ->queryAll();
        $li = "";
        if (empty($data))
            return "";
        foreach ($data as $row) {
            $ul = self::getHierarchy($row['id'], $selected);
            $sel = "";
            if (in_array($row['id'], $selected))
                $sel = "checked";
            $input = "<input type=\"checkbox\" name=\"User[category][]\" value=\"{$row['id']}\" class=\"group-check\" {$sel}>";
//            if (empty($ul))
//                $input = "";
            $li .= "<li>{$input} <a href=\"#\">{$row['name']}</a>{$ul}</li>";
        }

        return "<ul class=\"category-tree\">{$li}</ul>";
    }

    public static function getProductsGroupHierarchy($id = 0, $selected = null) {
        if (empty($id))
            $sql = "select * from (select p.id,p.name,(select count(*) from products where parentId=p.id) as count from products p where parentId=:id order by name asc) q1 where q1.count>0 order by name";
        else
            $sql = "select name,id,productType from products where parentId=:id order by name asc";
        $data = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $id, PDO::PARAM_STR)
            ->queryAll();
        $li = "";
        if (empty($data))
            return "";
        foreach ($data as $row) {
            $ul = self::getProductsGroupHierarchy($row['id'], $selected);
            $sel = "";
            if (in_array($row['id'], $selected))
                $sel = "checked";
            $productType = $row['productType'];
            if (isset($row['productType']) && !empty($row['productType']))
                $input = "<input type=\"checkbox\" name=\"ProductGroups[productIds][]\" value=\"{$row['id']}\" class=\"group-check\" {$sel}>";
            else
                $input = "";
//            if (empty($ul))
//                $input = "";
            $li .= "<li>{$input} <a href=\"#\">{$row['name']}</a>{$ul}</li>";
        }

        return "<ul class=\"category-tree\">{$li}</ul>";
    }

    public static function getProductParents($user, $is_market = false)
    {
        $sql = "select p.id,p.parentId,p.name from products p
                where p.id in(select category_id from user_categories where user_id=:u) and p.productType=''
                order by p.name";
        return Yii::$app->db->createCommand($sql)
            ->bindValue(":u", $user, PDO::PARAM_INT)->queryAll();
    }

    public static function getProducts($id, $order, $user, $is_market = false)
    {
        $sql = "select p.id,p.parentId,p.name,p.price,p.mainUnit,oi.quantity, oi.prepared from products p
                 left join order_items oi on oi.productId=p.id and oi.orderId=:o
                 left join product_groups_link pgl ON p.id = pgl.productId
                left join product_groups pg ON pg.id = pgl.productGroupId
                where p.id in(select category_id from user_categories where user_id=:u) and p.parentId=:p and p.productType!='' and (pg.is_market=:m or pg.is_market is null)
                group by p.id
                order by p.name";
        return Yii::$app->db->createCommand($sql)
            ->bindValue(":p", $id, PDO::PARAM_STR)
            ->bindValue(":o", $order, PDO::PARAM_INT)
            ->bindValue(":u", $user, PDO::PARAM_INT)
            ->bindValue(":m", $is_market ? 1 : 0, PDO::PARAM_INT)
            ->queryAll();
    }

    public static function getUserProducts()
    {
        $r = [];
        $data = Yii::$app->db->createCommand("select category_id from user_categories where user_id=:id")
            ->bindValue(":id", Yii::$app->user->id, PDO::PARAM_INT)
            ->queryColumn();
        foreach ($data as $row) {
            $r = array_merge($r, self::getUserTree($row));
        }
        return $r;
    }

    public static function getUserTree($id = 0)
    {
        $r = [];
        $sql = "select id from products where parentId=:id";
        $data = Yii::$app->db->createCommand($sql)
            ->bindParam(":id", $id, PDO::PARAM_STR)
            ->queryColumn();
        foreach ($data as $row) {
            $r[] = $row;
            $r = array_merge($r, self::getUserTree($row));
        }

        return $r;
    }

    /**
     * Получить единицу измерения продукта
     * @return string
     */
    public function getUnit()
    {
        return $this->mainUnit ?? 'шт';
    }

}
