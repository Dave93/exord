<?php

namespace app\controllers;

use app\models\Iiko;
use yii\db\mssql\PDO;
use yii\web\Controller;

class ParserController extends Controller
{
    public function actionProducts()
    {
        $iiko = new Iiko();
        $iiko->auth();
        $data = $iiko->getProducts();
        $db = \Yii::$app->db;
        foreach ($data['products'] as $row) {
            if ($row['type'] == 'dish')
                continue;
            $sql = "insert into products(id,code,`order`,`name`,description,measureUnit,price,`type`,isIncludedInMenu,isDeleted,syncDate) 
                      values(:id,:c,:o,:n,:d,:m,:p,:t,:in,:isd,:sd) on duplicate key
                       update `name`=values(`name`),description=values(description),measureUnit=values(measureUnit),price=values(price),`type`=values(`type`),
                       isIncludedInMenu=values(isIncludedInMenu),isDeleted=values(isDeleted),syncDate=values(syncDate)";
            $db->createCommand($sql)
                ->bindValue(":id", $row['id'], PDO::PARAM_STR)
                ->bindValue(":c", $row['code'], PDO::PARAM_INT)
                ->bindValue(":o", $row['order'], PDO::PARAM_INT)
                ->bindValue(":n", $row['name'], PDO::PARAM_STR)
                ->bindValue(":d", $row['description'], PDO::PARAM_STR)
                ->bindValue(":m", $row['measureUnit'], PDO::PARAM_STR)
                ->bindValue(":p", $row['price'], PDO::PARAM_STR)
                ->bindValue(":t", $row['type'], PDO::PARAM_STR)
                ->bindValue(":in", $row['isIncludedInMenu'], PDO::PARAM_STR)
                ->bindValue(":isd", $row['isDeleted'], PDO::PARAM_STR)
                ->bindValue(":sd", date("Y-m-d H:i:s"), PDO::PARAM_STR)
                ->execute();
        }
        return true;
    }
}
