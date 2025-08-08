<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Groups;
use app\models\Iiko;
use app\models\Terminals;
use app\models\User;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class PendingDeliveriesController extends Controller
{
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
                        'actions' => ['index', 'return', 'list', 'view', 'delete', 'close', 'cancel-order'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                        ],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'close' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex($start = null, $end = null, $terminal = null, $organization = null)
    {
        if ($start == null) {
            $start = date('Y-m-d');
        }

        if ($end == null) {
            $end = date('Y-m-d');
        }

        list($rmsLogin, $organizationId) = explode("|", $organization);

        $iiko = new Iiko();
        $iiko->rmsAuth($rmsLogin);

//        $terminals = Terminals::getList();
        $terminals = Groups::getList();
//        echo "<pre>"; print_r($terminals); echo "</pre>";
//        die();
        $terminalId = null;
        if (!empty($terminal)) {
            $terminalModel = Groups::findOne($terminal)->toArray();
            $terminalId = $terminalModel['id'];
        }

        $data = $iiko->pendingDeliveries($start, $end, $terminalId ? [
            $terminalId
        ] : [], $organizationId);


        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sum', 'timestamp', 'number'],
            ],
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);


        return $this->render(
            'index',
            [
                'start' => $start,
                'end' => $end,
                'dataProvider' => $dataProvider,
                'terminals' => $terminals,
                'terminal' => $terminal,
                'organization' => $organization
            ]
        );
    }

    public function actionCancelOrder($orderId = null, $organizationId = null, $removalTypeId = null, $login = null)
    {
        $iiko = new Iiko();
        $iiko->rmsAuth($login);
        $iiko->cancelDeliveryOrder($orderId, $organizationId, $removalTypeId);
        sleep(1);
        return $this->redirect(Yii::$app->request->referrer);
    }
}