<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Iiko;
use app\models\OrderItems;
use app\models\OrderItemSearch;
use app\models\Orders;
use app\models\OrderSearch;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class InvoiceController extends Controller
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
                            User::ROLE_COOK,
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
                    [
                        'actions' => ['office', 'office-view', 'approve'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_OFFICE
                        ],
                    ],
                    [
                        'actions' => ['auth'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN
                        ],
                    ]
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

    public function actionIndex($start = null, $end = null)
    {

        if ($start == null) {
            $start = date('Y-m-d');
        }

        if ($end == null) {
            $end = date('Y-m-d');
        }

        $query = Orders::find();

        $allowedUserIds = [
            75,
            76,
            84,
            93,
            94,
            106,
            108,
            109,
            113,
            114,
            118,
            119,
            130,
            131,
            77,
            82
        ];

//        if (!in_array(Yii::$app->user->identity->id, $allowedUserIds)) {
//            // where addDate is earlier than 33 hours ago
//            $query->andWhere('addDate>=:start', [':start' => date('Y-m-d H:i:s', strtotime('-33 hours'))]);
//            $query->andWhere('addDate<=:end', [':end' => date('Y-m-d H:i:s')]);
//        }

        $query->andFilterWhere([
            'userId' => Yii::$app->user->identity->id,
            'state' => 1
        ]);

        $query->orFilterWhere([
            'try_again' => 1
        ]);

        $model = $query->orderBy('id DESC')->one();
        if (!$model) {
            return $this->render('error', [
                'message' => 'У вас нет накладных. Обратитесь к представителям склада.'
            ]);
        }

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $orderItems = OrderItems::find()->where(['orderId' => $model->id])->all();
            foreach ($data['data'] as $productId => $datum) {
                foreach ($orderItems as $orderItem) {
                    if ($productId == $orderItem->productId) {
                        $orderItem->factStoreQuantity = $datum['factStoreQuantity'];
                        $orderItem->returned_quantity = $datum['returnQuantity'];
                        $orderItem->save();
                    }
                }
            }

            $model->state = 3;
            $model->try_again = 0;
            $model->save();

            return $this->redirect(['/']);
        }

        $searchModel = new OrderItemSearch();
        $searchModel->orderId = $model->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $orderItems = OrderItems::find()->where(['orderId' => $id])->all();
            foreach ($data['data'] as $productId => $datum) {
                foreach ($orderItems as $orderItem) {
                    if ($productId == $orderItem->productId) {
                        $orderItem->factStoreQuantity = $datum['factStoreQuantity'];
                        $orderItem->save();
                    }
                }
            }
        }


        if (in_array(Yii::$app->user->identity->role, [User::ROLE_BARMEN, User::ROLE_COOK, User::ROLE_PASTRY]))
            $model = Orders::findOne(['id' => $id, 'userId' => Yii::$app->user->id]);
        else
            $model = Orders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $searchModel = new OrderItemSearch();
        $searchModel->orderId = $model->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('view', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionOffice($start = null, $end = null)
    {

        if ($start == null) {
            $start = date('Y-m-d');
        }

        if ($end == null) {
            $end = date('Y-m-d');
        }

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false, $start, $end, 3);

        $arData = $dataProvider->getModels();
        $arOrderIds = [];

        foreach ($arData as $key => $value) {
            $arOrderIds[] = $value['id'];
        }

        $orderItems = OrderItems::find()->where(['in', 'orderId', $arOrderIds])->all();

        $arIncorrectOrders = [];

        /**
         * @var $orderItems OrderItems[]
         */
        foreach ($orderItems as $orderItem) {
            if ($orderItem->factStoreQuantity != $orderItem->shipped_from_warehouse) {
                $arIncorrectOrders[$orderItem->orderId] = $orderItem->orderId;
            }
        }

        return $this->render('office', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'start' => $start,
            'end' => $end,
            'arIncorrectOrders' => array_values($arIncorrectOrders)
        ]);
    }

    public function actionOfficeView($id) {
        $model = Orders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }



        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $orderItems = OrderItems::find()->where(['orderId' => $id])->all();
            foreach ($data['data'] as $productId => $datum) {
                foreach ($orderItems as $orderItem) {
                    if ($productId == $orderItem->productId) {
                        $orderItem->factOfficeQuantity = $datum['factOfficeQuantity'];
                        $orderItem->save();
                    }
                }
            }

            $iiko = new Iiko();
            $iiko->auth();

//            $outDoc = $iiko->supplierOutStockDoc($model);
//echo '<pre>'; print_r($outDoc); echo '</pre>';die();
            $model->office_comment = $data['Orders']['office_comment'];
            $model->state = 2;
            $model->save();
            return $this->redirect(['/invoice/office']);
        }

        $searchModel = new OrderItemSearch();
        $searchModel->orderId = $model->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('office-view', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionApprove($id, $back = '/invoice/office') {
        $model = Orders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $orderItems = OrderItems::find()->where(['orderId' => $id])->all();
        foreach ($orderItems as $orderItem) {
            $orderItem->factOfficeQuantity = $orderItem->factStoreQuantity;
            $orderItem->save();
        }

        $model->state = 2;
        $model->save();
        return $this->redirect([$back]);
    }

    public function actionAuth($id) {
        $user = User::findOne($id);
        if ($user == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        \Yii::$app->user->login($user);
        return $this->redirect(['/invoice/index']);
    }
}