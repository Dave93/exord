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

        // Check if single day is selected
        $isSingleDay = ($dateFrom === $dateTo);
        $detailedData = [];

        if ($isSingleDay) {
            // If single day, get detailed data with creation dates
            $query = (new Query())
                ->select([
                    's.name as terminal_name',
                    's.id as store_id',
                    'oi.return_amount_kg',
                    'oi.return_amount',
                    'oi.created_at',
                    'oi.id'
                ])
                ->from(['oi' => OilInventory::tableName()])
                ->leftJoin(['s' => Stores::tableName()], 'BINARY s.id = BINARY oi.store_id')
                ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
                ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
                ->andWhere(['>', 'oi.return_amount_kg', 0])
                ->orderBy(['s.name' => SORT_ASC, 'oi.created_at' => SORT_ASC]);

            $detailedData = $query->all();

            // Group by store for summary
            $groupedData = [];
            foreach ($detailedData as $row) {
                $storeId = $row['store_id'];
                if (!isset($groupedData[$storeId])) {
                    $groupedData[$storeId] = [
                        'terminal_name' => $row['terminal_name'],
                        'store_id' => $storeId,
                        'total_return_kg' => 0,
                        'total_return_liters' => 0,
                        'records' => []
                    ];
                }
                $groupedData[$storeId]['total_return_kg'] += $row['return_amount_kg'];
                $groupedData[$storeId]['total_return_liters'] += $row['return_amount'];
                $groupedData[$storeId]['records'][] = [
                    'created_at' => $row['created_at'],
                    'return_amount_kg' => $row['return_amount_kg'],
                    'return_amount' => $row['return_amount']
                ];
            }
            $data = array_values($groupedData);
        } else {
            // Original query for date range
            $query = (new Query())
                ->select([
                    's.name as terminal_name',
                    's.id as store_id',
                    'SUM(oi.return_amount_kg) as total_return_kg',
                    'SUM(oi.return_amount) as total_return_liters'
                ])
                ->from(['oi' => OilInventory::tableName()])
                ->leftJoin(['s' => Stores::tableName()], 'BINARY s.id = BINARY oi.store_id')
                ->where(['between', 'DATE(oi.created_at)', $dateFrom, $dateTo])
                ->andWhere(['oi.status' => OilInventory::STATUS_ACCEPTED])
                ->groupBy(['s.id', 's.name'])
                ->orderBy(['s.name' => SORT_ASC]);

            $data = $query->all();
        }

        $totalReturnKg = array_sum(array_column($data, 'total_return_kg'));
        $totalReturnLiters = array_sum(array_column($data, 'total_return_liters'));

        return $this->render('index', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalReturnKg' => $totalReturnKg,
            'totalReturnLiters' => $totalReturnLiters,
            'isSingleDay' => $isSingleDay,
        ]);
    }
}