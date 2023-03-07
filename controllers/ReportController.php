<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Stock;
use app\models\Stores;
use app\models\User;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\mssql\PDO;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * StockController implements the CRUD actions for Stock model.
 */
class ReportController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
                'only' => ['*'],
                'rules' => [
                    [
                        'actions' => ['price', 'price-excel', 'spending', 'spending-excel'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionPrice($start = null, $end = null, $product = null, $supplier = null)
    {
        if ($start == null)
            $start = date("Y-m-01");
        if ($end == null) {
            $end = date("Y-m-t");
        }
        $send = date("Y-m-d", strtotime($end . " +1 day"));

        $data = [];
        $suppliers = [];
        $mean = [];

        $sql = "select d.productId,p.name as product,d.supplierId,s.name as supplier,date(d.date) as `date`, round(sum(d.price)/count(*),2) as price from docs d 
                left join products p on p.id=d.productId
                left join suppliers s on s.id=d.supplierId
                where d.date between :s and :e and d.productId=:p and s.client=1
                group by d.supplierId,date(d.date)";
        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':s', $start, PDO::PARAM_STR)
            ->bindValue(':e', $send, PDO::PARAM_STR)
            ->bindValue(':p', $product, PDO::PARAM_STR)
            ->queryAll();
        foreach ($rows as $row) {
            $data[$row['date']][$row['supplierId']] = $row['price'];
            if (!in_array($row['supplierId'], $suppliers))
                $suppliers[] = $row['supplierId'];
        }

        foreach ($data as $key => $row) {
            $i = 0;
            $t = 0;
            foreach ($row as $r) {
                $i++;
                $t += $r;
            }
            $mean[$key] = round($t / $i);
        }

        return $this->render('price', [
            'start' => $start,
            'end' => $end,
            'product' => $product,
            'supplier' => $supplier,
            'data' => $data,
            'mean' => $mean,
            'suppliers' => $suppliers,
        ]);
    }

    public function actionPriceExcel($start = null, $end = null, $product = null, $supplier = null)
    {
        if ($start == null)
            $start = date("Y-m-01");
        if ($end == null) {
            $end = date("Y-m-t");
        }
        $send = date("Y-m-d", strtotime($end . " +1 day"));

        $data = [];
        $suppliers = [];
        $mean = [];

        $sql = "select d.productId,p.name as product,d.supplierId,s.name as supplier,date(d.date) as `date`, round(sum(d.price)/count(*),2) as price from docs d 
                left join products p on p.id=d.productId
                left join suppliers s on s.id=d.supplierId
                where d.date between :s and :e and d.productId=:p and s.client=1
                group by d.supplierId,date(d.date)";
        $rows = Yii::$app->db->createCommand($sql)
            ->bindValue(':s', $start, PDO::PARAM_STR)
            ->bindValue(':e', $send, PDO::PARAM_STR)
            ->bindValue(':p', $product, PDO::PARAM_STR)
            ->queryAll();

        foreach ($rows as $row) {
            $data[$row['date']][$row['supplierId']] = $row['price'];
            if (!in_array($row['supplierId'], $suppliers))
                $suppliers[] = $row['supplierId'];
        }

        foreach ($data as $key => $row) {
            $i = 0;
            $t = 0;
            foreach ($row as $r) {
                $i++;
                $t += $r;
            }
            $mean[$key] = round($t / $i);
        }

        $content = $this->renderPartial('price-excel', [
            'start' => $start,
            'end' => $end,
            'product' => $product,
            'supplier' => $supplier,
            'data' => $data,
            'mean' => $mean,
            'suppliers' => $suppliers,
        ]);

        $file = "Изменение цен.xls";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;');
        header('Content-Disposition: attachment;filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        Yii::$app->response->data = $content;
    }

    public function actionSpending($start = null, $end = null, $coefficient = null)
    {
        $model = Stores::findOne('aafd23ee-e90f-492d-b187-98a80ea1f568');
        $model->updateBalance();

        if ($start == null)
            $start = date("Y-m-01");
        if ($end == null) {
            $end = date("Y-m-t");
        }
        if ($coefficient == null) {
            $coefficient = 1;
        }
        $send = date("Y-m-d", strtotime($end . " +1 day"));

        $sql = 'select * from (select d.productId,p.mainUnit as unit,p.name,round(if(s.amount>0,s.amount,0),2) as stock,round(sum(d.amount),2) as total from outdocs d
                inner join products p on p.id=d.productId
                left join stock_balance s on s.product=d.productId and s.store="00fab3b1-c895-45b8-b0b5-474965287768"
                where d.date between :s and :e
                group by d.productId) q1 order by q1.name';

        $data = Yii::$app->db->createCommand($sql)
            ->bindValue(':s', $start, PDO::PARAM_STR)
            ->bindValue(':e', $send, PDO::PARAM_STR)
            ->queryAll();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['name', 'stock', 'total', 'unit'],
            ],
            'pagination' => false,
        ]);

        return $this->render('spending', [
            'start' => $start,
            'end' => $end,
            'coefficient' => $coefficient,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionSpendingExcel($start = null, $end = null, $coefficient = null)
    {
        if ($start == null)
            $start = date("Y-m-01");
        if ($end == null) {
            $end = date("Y-m-t");
        }
        if ($coefficient == null) {
            $coefficient = 1;
        }
        $send = date("Y-m-d", strtotime($end . " +1 day"));

        $sql = 'select * from (select d.productId,p.mainUnit as unit,p.name,round(if(s.amount>0,s.amount,0),2) as stock,round(sum(d.amount),2) as total from outdocs d
                inner join products p on p.id=d.productId
                left join stock_balance s on s.product=d.productId and s.store="00fab3b1-c895-45b8-b0b5-474965287768"
                where d.date between :s and :e
                group by d.productId) q1 order by q1.name';

        $data = Yii::$app->db->createCommand($sql)
            ->bindValue(':s', $start, PDO::PARAM_STR)
            ->bindValue(':e', $send, PDO::PARAM_STR)
            ->queryAll();

        $content = $this->renderPartial('spending-excel', [
            'start' => $start,
            'end' => $end,
            'coefficient' => $coefficient,
            'data' => $data,
        ]);

        $file = "Расходы({$start}-{$end}).xls";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;');
        header('Content-Disposition: attachment;filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        Yii::$app->response->data = $content;
    }
}
