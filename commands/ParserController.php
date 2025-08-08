<?php

namespace app\commands;

use app\models\Iiko;
use app\models\Stores;
use app\models\TelegramBot;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\mssql\PDO;

class ParserController extends Controller
{
    public function actionIncoming()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('display_errors', 1);

        $iiko = new Iiko();
        $iiko->auth();
        $today = date("Y-m-d");
        $start = date("Y-m-d", strtotime($today . ' -3 days'));
        $end = date('Y-m-d', strtotime($today . ' +1 day'));
        $start = '2019-12-01';
        $data = Yii::$app->db->createCommand("select id from suppliers where client=1")->queryColumn();
        foreach ($data as $id) {
            $iiko->incoming($start, $end, $id);
        }

        return ExitCode::OK;
    }

    public function actionOutgoing()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('display_errors', 1);

        $iiko = new Iiko();
        $iiko->auth();
        $today = date("Y-m-d");
        $start = date("Y-m-d", strtotime($today . ' -3 days'));
        $end = date('Y-m-d', strtotime($today . ' +1 day'));
        $iiko->outgoing($start, $end);

        return ExitCode::OK;
    }

    public function actionBalance()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('display_errors', 1);

        $bot = new TelegramBot();
        $store = 'aafd23ee-e90f-492d-b187-98a80ea1f568';
        $model = Stores::findOne($store);
        $model->updateBalance();

        $i = 0;
        $text = "";
        $sql = 'select p.name,b.amount,p.minBalance from stock_balance b 
                left join products p on p.id=b.product
                where b.store=:s and p.minBalance>0 and b.amount<p.minBalance';
        $data = Yii::$app->db->createCommand($sql)->bindValue(':s', $store, PDO::PARAM_STR)->queryAll();
        foreach ($data as $row) {
            $i++;
            $text .= "{$i}. {$row['name']} -> {$row['amount']} ({$row['minBalance']})\n";
        }
        if (empty($text)) {
            return ExitCode::OK;
        }
        $text = "<b>Продукты</b>\n" . $text;
        $bot->sendMessage(-1001879316029, $text, 'HTML');

        return ExitCode::OK;
    }

    public function actionPrice()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('display_errors', 1);

        $bot = new TelegramBot();

        $i = 0;
        $text = "";
        $updateSql = 'update docs set isNotify=1 where productId=:p and date(date)=:d';
        $sql = 'select q1.*,s.name as supplier,p.name as product from (select d.documentId,d.supplierId,d.productId,max(d.price) as price,(select round(sum(d1.price)/count(*),2) from docs d1 where d1.productId=d.productId and d1.date<:d order by d1.date desc limit 3) as average from docs d 
                where date(d.date)=:d and d.isNotify=0
                group by d.productId) q1
                left join suppliers s on s.id=q1.supplierId
                left join products p on p.id=q1.productId
                where q1.price>q1.average*1.1';
        $data = Yii::$app->db->createCommand($sql)->bindValue(':d', date("Y-m-d"), PDO::PARAM_STR)->queryAll();
        foreach ($data as $row) {
            $i++;
            $text .= "{$i}. {$row['product']}\n{$row['supplier']}\nСредняя цена: {$row['average']}\nЦена: {$row['price']}\n\n";
            Yii::$app->db->createCommand($updateSql)
                ->bindValue(':p', $row['productId'], PDO::PARAM_STR)
                ->bindValue(':d', date("Y-m-d"), PDO::PARAM_STR)
                ->execute();
        }
        if (empty($text)) {
            return ExitCode::OK;
        }
        $text = "<b>Завышенные цены</b>\n" . $text;
        $bot->sendMessage(-1001879316029, $text, 'HTML');

        return ExitCode::OK;
    }

    public function actionIncomingPrice()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('memory_limit', -1);
        ini_set('display_errors', 1);

        $now = date('Y-m-d');
        $start = date('Y-m-d', strtotime($now . ' -7 days'));

        $iiko = new Iiko();
        $iiko->auth();
        $iiko->incomingPrices($start, $now);

        return ExitCode::OK;
    }

    public function actionAveragePrice()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('memory_limit', -1);
        ini_set('display_errors', 1);

        $iiko = new Iiko();
        $iiko->auth();
        $data = $iiko->getReport('aafd23ee-e90f-492d-b187-98a80ea1f568');
        $sql = "update products set price=:p,priceSyncDate=:d where id=:id and productType='PREPARED'";
        foreach ($data as $row) {
            $product = (string)$row['product'];
            $price = (double)$row['sum'] / (double)$row['amount'];
            Yii::$app->db->createCommand($sql)
                ->bindValue(":id", $product, PDO::PARAM_STR)
                ->bindValue(":p", $price, PDO::PARAM_STR)
                ->bindValue(":d", date('Y-m-d H:i:s'), PDO::PARAM_STR)
                ->execute();
        }

        return ExitCode::OK;
    }
}
