<?php

namespace app\controllers;

use Yii;
use app\models\OilInventory;
use app\models\Stores;
use app\models\User;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\components\AccessRule;
use yii\db\Query;

class OilReturnsSummaryController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['*'],
                'ruleConfig' => [
                    'class' => AccessRule::class,
                ],
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-d', strtotime('-7 days')));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));

        $query = (new Query())
            ->select([
                's.name as terminal_name',
                's.id as store_id',
                'SUM(oi.return_amount_kg) as total_return_kg',
                'SUM(oi.return_amount) as total_return_liters'
            ])
            ->from(['oi' => OilInventory::tableName()])
            ->leftJoin(['s' => Stores::tableName()], 's.id = oi.store_id')
            ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
            ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
            ->groupBy(['s.id', 's.name'])
            ->orderBy(['s.name' => SORT_ASC]);

        $data = $query->all();

        $totalReturnKg = array_sum(array_column($data, 'total_return_kg'));
        $totalReturnLiters = array_sum(array_column($data, 'total_return_liters'));

        return $this->render('index', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalReturnKg' => $totalReturnKg,
            'totalReturnLiters' => $totalReturnLiters,
        ]);
    }
}