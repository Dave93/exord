<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Availability;
use app\models\Dashboard;
use app\models\Iiko;
use app\models\OrderItems;
use app\models\OrderItemSearch;
use app\models\Orders;
use app\models\OrderSearch;
use app\models\Settings;
use app\models\Stores;
use app\models\TelegramBot;
use app\models\User;
use app\models\Zone;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\mssql\PDO;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use kartik\mpdf\Pdf;

class AvailabilityController extends Controller
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
                        'actions' => ['index', 'return', 'list', 'view', 'delete', 'close'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                        ],
                    ],
                    [
                        'actions' => ['customer-orders', 'customer-history', 'create', 'update', 'send', 'fact-stock', 'fact-supplier', 'view'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_COOK,
                            User::ROLE_BARMEN,
                            User::ROLE_PASTRY,
                            User::ROLE_MANAGER,
                        ],
                    ],
                    [
                        'actions' => ['update', 'stock-orders', 'stock', 'stock-order', 'stock-update', 'stock-close', 'send', 'stock-fact-supplier', 'stock-by-product', 'stock-by-product-excel', 'stock-excel', 'stock-excel-zone', 'order-excel', 'view', 'close', 'invoice'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_STOCK
                        ],
                    ],
                    [
                        'actions' => ['buyer-orders', 'buyer', 'buyer-by-product', 'supplier-excel', 'view'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_BUYER
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

    public function actionIndex()
    {
        $allAvailability = Availability::find()->all();

        if (Yii::$app->request->isPost) {
            $available = Yii::$app->request->post("available");
            Availability::deleteAll();
            foreach ($available as $key => $value) {
                $av = new Availability();
                $av->productId = $value;
                $av->save();
            }
        }

        return $this->render('index', [
            'allAvailability' => ArrayHelper::getColumn($allAvailability, 'productId')
        ]);
    }
}
