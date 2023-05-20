<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\db\mssql\PDO;
use yii\helpers\ArrayHelper;

class Dashboard extends Model
{
    public static $yesNo = [
        "Нет",
        "Да"
    ];

    public static $months = [
        'Январь',
        'Февраль',
        'Март',
        'Апрель',
        'Май',
        'Июнь',
        'Июль',
        'Августь',
        'Сентябрь',
        'Октябрь',
        'Ноябрь',
        'Декабрь',
    ];

    public static function isNavActive($controller, $action = '')
    {
        $yiiController = \Yii::$app->controller->id;
        $yiiAction = \Yii::$app->controller->action->id;
        if ($yiiController == $controller) {
            if (!empty($action) && $yiiAction != $action)
                return false;
            return true;
        }
        return false;
    }

    public static function getMenu()
    {
        $menu = null;
        switch (Yii::$app->user->identity->role) {
            case User::ROLE_ADMIN:
                $menu = Yii::$app->controller->renderPartial('//menu/admin');
                break;
            case User::ROLE_OFFICE:
                $menu = Yii::$app->controller->renderPartial('//menu/office');
                break;
//            case User::ROLE_MANAGER:
//                $menu = Yii::$app->controller->renderPartial('//menu/manager');
//                break;
            case User::ROLE_STOCK:
                $menu = Yii::$app->controller->renderPartial('//menu/stock');
                break;
            case User::ROLE_BUYER:
                $menu = Yii::$app->controller->renderPartial('//menu/buyer');
                break;
            case User::ROLE_MANAGER:
            case User::ROLE_BARMEN:
            case User::ROLE_COOK:
            case User::ROLE_PASTRY:
                $menu = Yii::$app->controller->renderPartial('//menu/cook');
                break;
        }
        return $menu;
    }

    public static function getOperatorStats()
    {
        $closed = [];
        $created = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $i + 1;
            $start = date("Y-{$m}-01");
            $end = date("Y-{$m}-t", strtotime("+1 day"));
            $active = Yii::$app->db->createCommand("select count(*) from calls where state=:st and operator_id=:o and call_date>=:s and call_date<:e")
                ->bindValue(':o', Yii::$app->user->id, PDO::PARAM_INT)
                ->bindValue(':st', Calls::STATE_CLOSED, PDO::PARAM_INT)
                ->bindValue(':s', $start, PDO::PARAM_STR)
                ->bindValue(':e', $end, PDO::PARAM_STR)
                ->queryScalar();
            $nonactive = Yii::$app->db->createCommand("select count(*) from calls where state=:st and operator_id=:o and call_date>=:s and call_date<:e")
                ->bindValue(':o', Yii::$app->user->id, PDO::PARAM_INT)
                ->bindValue(':st', Calls::STATE_CREATED, PDO::PARAM_INT)
                ->bindValue(':s', $start, PDO::PARAM_STR)
                ->bindValue(':e', $end, PDO::PARAM_STR)
                ->queryScalar();
            $closed[$i] = $active;
            $created[$i] = $nonactive;
        }
        return [
            'closed' => $closed,
            'created' => $created,
        ];
    }

    public static function getLastCalls()
    {
        return Yii::$app->db->createCommand("select call_date,reason_info,company_name from calls where operator_id=:o order by call_date desc limit 5")
            ->bindValue(":o", Yii::$app->user->id, PDO::PARAM_INT)
            ->queryAll();
    }

    public static function getTodayStat()
    {
        $start = date("Y-m-d");
        $end = date("Y-m-d", strtotime("+1 day"));
        return Yii::$app->db->createCommand("select count(*) as total, company_name as company from calls where call_date>=:s and call_date<:e and state=1 group by company_name")
            ->bindValue(":s", $start, PDO::PARAM_STR)
            ->bindValue(":e", $end, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function getMonthStat()
    {
        $start = date("Y-m-01");
        $end = date("Y-m-t", strtotime("+1 day"));
        return Yii::$app->db->createCommand("
                        select count(*) as total, DATE(call_date) as date from calls 
                        where call_date between :s and :e
                        group by DATE(call_date)
                        order by call_date asc")
            ->bindValue(":s", $start, PDO::PARAM_STR)
            ->bindValue(":e", $end, PDO::PARAM_STR)
            ->queryAll();
    }

    public static function isOrderMan()
    {
        return in_array(Yii::$app->user->identity->role, [User::ROLE_BARMEN, User::ROLE_COOK, User::ROLE_PASTRY, User::ROLE_STOCK, User::ROLE_MANAGER]);
    }

    public static function price($total)
    {
        return number_format($total, 2, ".", " ");
    }

    public static function clearPrice($total)
    {
        return number_format($total, 0, ".", " ");
    }

    public static function getColumn($i)
    {
        $cols = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z',
        ];
        return $cols[$i];
    }

    public static function dateTime($date)
    {
        return date("d.m.Y H:i", strtotime($date));
    }

    public static function getIncomingProducts()
    {
        $data = Yii::$app->db->createCommand('select * from (select p.id,p.name from docs d inner join products p on p.id=d.productId group by d.productId) q1 order by q1.name')->queryAll();
        return ArrayHelper::map($data, 'id', 'name');
    }


}