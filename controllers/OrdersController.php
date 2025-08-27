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
use app\models\Terminals;
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


/**
 * OrdersController implements the CRUD actions for Orders model.
 */
class OrdersController extends Controller
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
                        'actions' => ['index', 'return', 'list', 'view', 'delete', 'close', 'try-again', 'return-back'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                            User::ROLE_OFFICE
                        ],
                    ],
                    [
                        'actions' => ['customer-orders', 'customer-history', 'create', 'update', 'send', 'fact-stock', 'fact-supplier', 'view', 'preview-invoice'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_COOK,
                            User::ROLE_BARMEN,
                            User::ROLE_PASTRY,
                            User::ROLE_MANAGER,
                            User::ROLE_ADMIN,
                        ],
                    ],
                    [
                        'actions' => ['update', 'delete-from-stock', 'add-items-to-stock', 'stock-orders', 'stock', 'stock-order', 'stock-update', 'stock-close', 'send', 'stock-fact-supplier', 'stock-by-product', 'stock-by-product-excel', 'stock-excel', 'stock-excel-zone', 'order-excel', 'view', 'close', 'invoice'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_STOCK,
                            User::ROLE_ADMIN,
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
                        'actions' => ['stock', 'prepared-from-stock', 'minus-from-stock'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ETAJ
                        ]
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

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false, $start, $end);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'start' => $start,
            'end' => $end
        ]);
    }

    public function actionList($date)
    {
        $query = new Query();
        $cmd = $query->select("stores.id,stores.name,orders.id,orders.date,orders.storeId")
            ->from("orders")
            ->leftJoin("stores", "stores.id=orders.storeId")
            ->where("orders.date=:d", [":d" => $date])
            ->orderBy("stores.name");
        $orders = $cmd->all();

        return $this->render('list', [
            'date' => $date,
            'orders' => $orders,
        ]);
    }

    public function actionTryAgain($id, $back = '/orders/index')
    {
        $model = Orders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->try_again = 1;
        $model->save();
        return $this->redirect([$back]);
    }

    public function actionReturnBack($id, $back = '/orders/index')
    {
        $model = Orders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $model->deleted_at = null;
        $model->deleted_by = null;
        $model->save();
        return $this->redirect([$back]);
    }

    public function actionView($id)
    {
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


    //Barmen, Cook, Pastry Methods
    public function actionCustomerOrders()
    {
        $searchModel = new OrderSearch();
        $searchModel->userId = Yii::$app->user->id;
//        $searchModel->state = 0;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);
        $dataProvider->sort = ['defaultOrder' => ['id' => SORT_DESC]];

        return $this->render('customer-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCustomerHistory()
    {
        $searchModel = new OrderSearch();
        $searchModel->userId = Yii::$app->user->id;
        $searchModel->state = 2;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false);
        $dataProvider->sort = ['defaultOrder' => ['id' => SORT_ASC]];

        return $this->render('customer-history', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $webroot = Yii::getAlias('@webroot');
        $now = date("Y-m-d H:i:s");
        $start = date("Y-m-d 18:00");
        $end = date("Y-m-d 00:00", strtotime($now . " +1 day"));

        $model = new Orders();
        $model->date = date("Y-m-d");
        $model->storeId = Yii::$app->user->identity->store_id;
        $model->supplierId = Yii::$app->user->identity->supplier_id;
        $model->userId = Yii::$app->user->id;

//        if ($now >= $start && $now < $end) {
//            $model->date = date("Y-m-d", strtotime($now . " +1 day"));
//        }
        $allAvailability = Availability::find()->all();
        $av = ArrayHelper::getColumn($allAvailability, 'productId');


        if (Yii::$app->request->isPost) {
            
            $model->load(Yii::$app->request->post());
            $stockId = User::getStoreId();
            $supplierId = User::getSupplierId();

            $items = Yii::$app->request->post("Items");
            
            $available = Yii::$app->request->post("Available");
            $model->addDate = date("Y-m-d H:i:s");
            $model->state = 0;
            $arBazarItems = [];
            if (count($items) > 0 && $model->save()) {
                $arrytData = [];
                if (!empty(Yii::$app->user->identity->terminalId)) {
                    $terminal = Terminals::findOne(Yii::$app->user->identity->terminalId);
                    $arrytData = [
                        'terminal_id' => 'b9b407ae-4f2b-4b27-bf1d-551a56b0065b',
                        'order_number' => $model->id,
                        'customerName' => Yii::$app->user->identity->fullname,
                        'customerPhone' => Yii::$app->user->identity->phone,
                        'toLat' => (float)$terminal->latitude,
                        'toLon' => (float)$terminal->longitude,
                        'address' => $terminal->address,
                        'price' => 0,
                        'payment_method' => 'Наличными',
                        "comment" => "",
                        'orderItems' => []
                    ];
                }

                OrderItems::deleteAll(['orderId' => $model->id]);
                foreach ($items as $key => $value) {
//                    if (empty($value) && empty($available[$key]))
//                        continue;

                    if (empty($value))
                        continue;
                    $arBazarItems[$key] = $value;
                    $oi = new OrderItems();
                    $oi->orderId = $model->id;
                    $oi->productId = $key;
                    $oi->quantity = $value;
                    $oi->storeId = $stockId;
                    $oi->supplierId = $supplierId;
                    $oi->storeQuantity = 0;
                    $oi->supplierQuantity = $value;
                    $oi->available = $available[$key] ?? 0;
                    $oi->userId = Yii::$app->user->id;
                    $oi->save();
                    if (!empty(Yii::$app->user->identity->terminalId)) {
                        $arrytData['orderItems'][] = [
                            'productId' => $oi->productId,
                            'quantity' => $oi->quantity,
                            'price' => $oi->product->price,
                            'name' => $oi->product->name,
                        ];
                    }
                }

                // if (!empty(Yii::$app->user->identity->terminalId)) {
                //     // http post query of $arrytData
                //     $ch = curl_init();
                //     curl_setopt($ch, CURLOPT_URL, 'https://api.warehouse.arryt.uz/api/external/create-order');
                //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrytData));
                //     curl_setopt($ch, CURLOPT_POST, 1);
                //     curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

                //     $headers = array();

                //     $headers[] = 'Accept: application/json';
                //     $headers[] = 'Content-Type: application/json';
                //     $headers[] = 'Authorization: Bearer x6kngzqofvr0bhytbe07ul6o0tv8sx';

                //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                //     $result = curl_exec($ch);

                //     if (curl_errno($ch)) {
                //         echo 'Error:' . curl_error($ch);
                //     }
                //     curl_close($ch);
                //     $result = json_decode($result);
                // }
            //     if ($model->userId == 158) {
            //     echo "<pre>";
            //     print_r($items);
            //     echo "</pre>";
            //     die();
            // }

                // if (!empty($arBazarItems)) {
                //     $arBazarItemIds = [];
                //     foreach ($arBazarItems as $key => $value) {
                //         $arBazarItemIds[] = $key;
                //     }
                //     $query = new Query();
                //     $products = $query->select('products.id,product_groups.is_market')
                //         ->from('products')
                //         ->leftJoin('product_groups_link', 'product_groups_link.productId = products.id')
                //         ->leftJoin('product_groups', 'product_groups.id = product_groups_link.productGroupId')
                //         ->where(['products.id' => $arBazarItemIds, 'product_groups.is_market' => 1])
                //         ->all();
                //     // if (Yii::$app->user->id == 158) {
                //     //     echo "<pre>";
                //     //     print_r($arBazarItemIds);
                //     //     echo "</pre>";
                //     //     echo "<pre>";
                //     //     print_r($products);
                //     //     echo "</pre>";
                //     // }
                //     $arBazarItemIds = ArrayHelper::getColumn($products, 'id');
                    
                //     // Keep only the items that exist in $arBazarItemIds
                //     $arBazarItems = array_intersect_key($arBazarItems, array_flip($arBazarItemIds));

                //     // if (Yii::$app->user->id == 158) {
                //     //     echo "<pre>";
                //     //     print_r($arBazarItems);
                //     //     echo "</pre>";
                //     //     die();
                //     // }
                // }

                // if (!empty($arBazarItems)) {
                //     $bazarModel = new Orders();
                //     $bazarModel->date = date("Y-m-d");
                //     $bazarModel->storeId = Yii::$app->user->identity->store_id;
                //     $bazarModel->supplierId = Yii::$app->user->identity->supplier_id;
                //     $bazarModel->is_market = 1;
                //     $bazarModel->userId = Yii::$app->user->id;
                //     $bazarModel->addDate = date("Y-m-d H:i:s");
                //     $bazarModel->state = 0;
                //     if ($bazarModel->save()) {
                //         foreach ($arBazarItems as $key => $value) {
                //             if (empty($value))
                //                 continue;
                //             $oi = new OrderItems();
                //             $oi->orderId = $bazarModel->id;
                //             $oi->productId = $key;
                //             $oi->quantity = $value;
                //             $oi->storeId = $stockId;
                //             $oi->supplierId = $supplierId;
                //             $oi->storeQuantity = 0;
                //             $oi->supplierQuantity = $value;
                //             $oi->available = $available[$key] ?? 0;
                //             $oi->userId = Yii::$app->user->id;
                //             $oi->save();
                //         }
                //     }
                // }

                $d = date("d.m.Y", strtotime($model->date));
                $text = "Поступил новый заказ: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата поставки: {$d}";

                $content = $this->renderPartial('preview-invoice', [
                    'model' => $model
                ]);

                $pdf = new \kartik\mpdf\Pdf([
                    'mode' => \kartik\mpdf\Pdf::MODE_UTF8, // leaner size using standard fonts
                    'content' => $content,
                ]);
                $filePath = '/uploads/'.sha1($model->id).'.pdf';

                $pdf->output($content, $webroot.$filePath, 'F');

                $bot = new TelegramBot();
                $message = $bot->sendDocument(-1001879316029, 'https://les.iiko.uz/'.$filePath);
                $bot->sendMessage(-1001879316029, $text, 'HTML', false, $message['result']['message_id']);
                return $this->redirect(['orders/view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'availability' => $av
        ]);
    }
    public function actionAddItemsToStock($orderId, $storeId, $supplierId)
    {

        $model = Orders::findOne(['id' => $orderId]);
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            $items = Yii::$app->request->post("Items");
            if (count($items) > 0 && $model->save()) {

                foreach ($items as $key => $value) {
//                    if (empty($value) && empty($available[$key]))
//                        continue;
                    if (empty($value))
                        continue;
                    $oi = new OrderItems();
                    $oi->orderId = $model->id;
                    $oi->productId = $key;
                    $oi->quantity = $value;
                    $oi->storeId = $storeId;
                    $oi->supplierId = $supplierId;
                    $oi->storeQuantity = 0;
                    $oi->supplierQuantity = $value;
                    $oi->available = 0;
                    $oi->userId = Yii::$app->user->id;
                    $oi->save();
                }

                return $this->redirect(['orders/stock', 'tab' => $model->id]);
            }
        }

        return $this->render('add-items-to-stock', [
            'model' => $model
        ]);
    }

    public function actionUpdate($id)
    {
        $userId = Yii::$app->user->id;
//        $model = Orders::findOne(['id' => $id, 'userId' => $userId, 'editable' => 1]);
        $model = Orders::findOne(['id' => $id]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->request->isPost) {
            $stockId = Settings::getValue("stock-id");
            $supplierId = Settings::getValue("supplier-id");

            $items = Yii::$app->request->post("Items");
            $model->state = 0;
            if (count($items) > 0 && $model->save()) {
//                OrderItems::deleteAll(['orderId' => $model->id]);
                foreach ($items as $key => $value) {
                    $oi = OrderItems::findOne(['orderId' => $model->id, 'productId' => $key]);
                    if (empty($value)) {
                        if ($oi != null)
                            $oi->delete();
                        continue;
                    }
                    if ($oi == null) {
                        $oi = new OrderItems();
                        $oi->orderId = $model->id;
                        $oi->productId = $key;
                        $oi->storeId = $stockId;
                        $oi->supplierId = $supplierId;
                        $oi->storeQuantity = 0;
                    }
                    $oi->quantity = $value;
                    $oi->supplierQuantity = $value;
                    $oi->userId = $userId;
                    $oi->save();
                }
                return $this->redirect(['orders/view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'user_id' => $userId,
            'model' => $model,
        ]);
    }

    public function actionDeleteFromStock($orderId, $itemId) {
        $model = Orders::findOne(['id' => $orderId]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $oi = OrderItems::findOne(['orderId' => $orderId, 'productId' => $itemId]);
        if ($oi != null) {
            $oi->delete();
        }
        return $this->redirect(['orders/stock', 'tab' => $model->id]);
    }

    public function actionPreparedFromStock($orderId, $itemId) {
        $model = Orders::findOne(['id' => $orderId]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $oi = OrderItems::findOne(['orderId' => $orderId, 'productId' => $itemId]);
        if ($oi != null) {
            $oi->prepared = 1;
            $oi->save();
        }
        return $this->redirect(['orders/stock', 'tab' => $model->id]);
    }

    public function actionMinusFromStock($orderId, $itemId) {
        $model = Orders::findOne(['id' => $orderId]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $oi = OrderItems::findOne(['orderId' => $orderId, 'productId' => $itemId]);
        if ($oi != null) {
            $oi->storeQuantity = 0;
//                $oi->supplierQuantity = 0;
            $oi->shipped_from_warehouse = 0;
            $oi->minused = 1;
            $oi->save();
        }
        return $this->redirect(['orders/stock', 'tab' => $model->id]);
    }

    public function actionFactStock($id)
    {
        $userId = Yii::$app->user->id;
        $model = Orders::findOne(['id' => $id, 'userId' => $userId]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->request->isPost) {
            // stock-out-id
            // stock-in-id
            $items = Yii::$app->request->post("Items");
            foreach ($items as $key => $value) {
                if (empty($value))
                    continue;
                $oi = OrderItems::findOne(['orderId' => $model->id, 'productId' => $key]);
                $oi->factStoreQuantity = $value;
                $oi->save();
            }
            return $this->redirect(['orders/view', 'id' => $model->id]);
        }

        return $this->render('fact-stock', [
            'model' => $model,
        ]);
    }

    public function actionFactSupplier($id)
    {
        $userId = Yii::$app->user->id;
        $model = Orders::findOne(['id' => $id, 'userId' => $userId]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->request->isPost) {
            // supplier-in-id
            $items = Yii::$app->request->post("Items");
            foreach ($items as $key => $value) {
                if (empty($value))
                    continue;
                $oi = OrderItems::findOne(['orderId' => $model->id, 'productId' => $key]);
                $oi->factSupplierQuantity = $value;
                $oi->save();
            }
            return $this->redirect(['orders/view', 'id' => $model->id]);
        }

        return $this->render('fact-supplier', [
            'model' => $model,
        ]);
    }

    public function actionClose($id)
    {
        $model = $this->findModel($id);

        $iiko = new Iiko();
        if ($iiko->auth()) {
            if (Yii::$app->user->identity->role != User::ROLE_STOCK) {
                $out = $iiko->storeOutDoc($model);
                $in = $iiko->supplierInStockDoc($model);
            }
//            else {
//                $out = true;
//                $in = $iiko->supplierInDoc($model);
//            }
        }
        if ($in || $out) {
            $model->state = 2;
            $model->save();

            $d = date('d.m.Y H:i');
            $text = "Заказ закрыть: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата: {$d}";
            $bot = new TelegramBot();
            $bot->sendMessage(-1001879316029, $text, 'HTML');
        }
        return $this->redirect(['orders/view', 'id' => $model->id]);
    }

    public function actionSend($id)
    {
        $model = Orders::findOne(['id' => $id]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->state = 1;
        $model->save();
        return $this->redirect(['orders/stock', 'tab' => $model->id]);
    }


    //Stock Methods
    public function actionStockOrders()
    {
        $searchModel = new OrderSearch();
//        $searchModel->state = 0;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);

        return $this->render('stock-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionStock($tab = null)
    {
        $date = date("Y-m-d");
        $stockId = User::getStoreId();


        $query = new Query();
        $orders = $query->select("stores.id,stores.name,orders.id,orders.date,orders.storeId,orders.comment,orders.addDate,orders.state")
            ->from("orders")
            ->leftJoin("stores", "stores.id=orders.storeId")
            ->where("orders.state<1 and storeId!=:s and (is_market=0 or is_market is null) and deleted_at is null", [":s" => $stockId])
            ->orderBy("stores.name")
            ->all();

        Yii::$app->db->createCommand("update orders set editable=0 where state!=2 and editable=1")->execute();
        if (Yii::$app->request->isPost) {
            $noRedirect = Yii::$app->request->post("noRedirect");
            $orderId = Yii::$app->request->post("orderId");
            $items = Yii::$app->request->post("Items");
            $isSave = Yii::$app->request->post("save");
            $isSend = Yii::$app->request->post("send");
            $isDelete = Yii::$app->request->post("delete");
            $comment = Yii::$app->request->post("comment");

            if ($isDelete == 'Y' && Yii::$app->user->identity->role == User::ROLE_ADMIN) {
                $model = Orders::findOne(['id' => $orderId]);
                $model->deleted_at = date("Y-m-d H:i:s");
                $model->deleted_by = (string)Yii::$app->user->id;
                if (!$model->save()) {
                     die(print_r($model->firstErrors));
                }
                // OrderItems::deleteAll(['orderId' => $orderId]);
// http post query of $arrytData
                // $ch = curl_init();
                // curl_setopt($ch, CURLOPT_URL, 'https://api.warehouse.arryt.uz/api/external/delete-order');
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                //     'order_id' => (int)$orderId
                // ]));
                // curl_setopt($ch, CURLOPT_POST, 1);
                // curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

                $headers = array();

                $headers[] = 'Accept: application/json';
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: Bearer x6kngzqofvr0bhytbe07ul6o0tv8sx';

                // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                // $result = curl_exec($ch);

                // if (curl_errno($ch)) {
                //     echo 'Error:' . curl_error($ch);
                // }
                // curl_close($ch);
                // $result = json_decode($result);
                return $this->redirect(['orders/stock']);
            }

            foreach ($items as $key => $value) {
                $oi = OrderItems::findOne(['orderId' => $orderId, 'productId' => $key]);
//                $oi->storeId = $stockId;

                $oi->storeQuantity = $value['s'];
//                $oi->supplierQuantity = $value['b'];
                $oi->shipped_from_warehouse = $value['s'];
                if (!$oi->save()) {
                    return print_r($oi->firstErrors);
                }
            }

            $model = Orders::findOne(['id' => $orderId]);
            $model->comment = $comment;
            if ($isSend == 'Y') {
                $iiko = new Iiko();
                $iiko->auth();

                $model->sent_date = date("Y-m-d H:i:s");
//echo '<pre>'; print_r($model); echo '</pre>';
                $outDoc = $iiko->supplierOutStockDoc($model);
//                echo '<pre>'; print_r($outDoc); echo '</pre>';die();
                $model->state = 1;
            }
            $model->is_locked = 1;
            $model->save();
            if ($noRedirect) {
                return '';
            } else {
                return $this->redirect(['orders/stock', 'tab' => $orderId]);
            }
        }

        return $this->render('stock', [
            'date' => $date,
            'orders' => $orders,
            'orderId' => $tab,
        ]);
    }

    public function actionStockOrder()
    {
        error_reporting(E_ERROR);
        ini_set('display_errors', 1);
        ini_set('max_execution_time', 600);

        $model = new Orders();
        $model->date = date("Y-m-d");
        $model->storeId = Yii::$app->user->identity->store_id;
        $model->supplierId = Yii::$app->user->identity->supplier_id;
        $model->userId = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $stockId = Settings::getValue("stock-id");
            $supplierId = Settings::getValue("supplier2-id");

            $items = Yii::$app->request->post("Items");
            $model->addDate = date("Y-m-d H:i:s");
            $model->state = 0;
            if (count($items) > 0 && $model->save()) {
                OrderItems::deleteAll(['orderId' => $model->id]);
                foreach ($items as $key => $value) {
                    if (empty($value))
                        continue;
                    $oi = new OrderItems();
                    $oi->orderId = $model->id;
                    $oi->productId = $key;
                    $oi->quantity = $value;
                    $oi->storeId = $stockId;
                    $oi->supplierId = $supplierId;
                    $oi->storeQuantity = 0;
                    $oi->supplierQuantity = $value;
                    $oi->userId = Yii::$app->user->id;
                    $oi->save();
                }
                return $this->redirect(['orders/view', 'id' => $model->id]);
            }
        }

        return $this->render('stock-order', [
            'model' => $model,
            'categories' => User::getUserCategories(Yii::$app->user->id),
        ]);
    }

    public function actionStockUpdate($id)
    {
        $userId = Yii::$app->user->id;
        $model = Orders::findOne(['id' => $id, 'userId' => Yii::$app->user->id]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->request->isPost) {
            $items = Yii::$app->request->post("Items");
            $model->state = 0;
            if (count($items) > 0 && $model->save()) {
                OrderItems::deleteAll(['orderId' => $model->id]);
                foreach ($items as $key => $value) {
                    if (empty($value))
                        continue;
                    $oi = new OrderItems();
                    $oi->orderId = $model->id;
                    $oi->productId = $key;
                    $oi->quantity = $value;
                    $oi->storeQuantity = 0;
                    $oi->supplierQuantity = $value;
                    $oi->userId = $userId;
                    $oi->save();
                }
                return $this->redirect(['orders/view', 'id' => $model->id]);
            }
        }

        return $this->render('stock-update', [
            'user_id' => $userId,
            'model' => $model,
            'categories' => User::getUserCategories($userId),
        ]);
    }

    public function actionStockFactSupplier($id)
    {
        $userId = Yii::$app->user->id;
        $model = Orders::findOne(['id' => $id, 'userId' => $userId]);
        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->request->isPost) {
            // supplier-in-id
            $items = Yii::$app->request->post("Items");
            foreach ($items as $key => $value) {
                if (empty($value))
                    continue;
                $oi = OrderItems::findOne(['orderId' => $model->id, 'productId' => $key]);
                $oi->factSupplierQuantity = $value;
                $oi->save();
            }
            return $this->redirect(['orders/view', 'id' => $model->id]);
        }

        return $this->render('fact-supplier', [
            'model' => $model,
        ]);
    }

    public function actionStockExcel()
    {
        $date = date("Y-m-d");

        $orders = Orders::getOrders($date);
        $products = Orders::getOrderStockProducts();
//        return print_r($orders);
        $content = $this->renderPartial('stock-excel', [
            'orders' => $orders,
            'products' => $products,
        ]);

        $file = "Итого.xls";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;');
        header('Content-Disposition: attachment;filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        Yii::$app->response->data = $content;
    }

    public function actionInvoice($id)
    {
        $model = $this->findModel($id);
        $content = $this->renderPartial('invoice', [
            'model' => $model
        ]);

        // setup kartik\mpdf\Pdf component
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            'filename' => 'Накладной.pdf',
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            'cssFile' => 'css/invoice.css',
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
//            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            // set mPDF properties on the fly
            'options' => ['title' => $model->id],
            // call mPDF methods on the fly
            'methods' => [
//                'SetHeader' => ['Остаток на складе'],
                'SetFooter' => ['{PAGENO}'],
            ]
        ]);

//        $stylesheet = file_get_contents(__DIR__.'/invoice.css');
//
//        $stylesheet = '<head><link rel="stylesheet" type="text/css" href="'.Yii::getAlias('@web').'\css\exam_report.css'.'"/></head>';
//        $pdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
//        $pdf->WriteHTML($stylesheet,Pdf\HTMLParserMode::HEADER_CSS);

        // return the pdf output as per the destination setting
        return $pdf->render();
    }

    public function actionPreviewInvoice($id)
    {
        $model = $this->findModel($id);
        return $this->render('preview-invoice', [
            'model' => $model
        ]);
    }

    public function actionStockExcelZone()
    {
        $i = 0;
        $date = date("Y-m-d");
        $spreadsheet = new Spreadsheet();

        $zones = Zone::find()->orderBy(['name' => SORT_ASC])->asArray()->all();
        $productSql = "select i.productId,p.name,p.mainUnit from orders o
                        left join order_items i on i.orderId=o.id
                        left join products p on p.id=i.productId
                        where o.state!=2 and p.zone=:z
                        group by i.productId";

        $quantitySql = "select o.storeId,i.productId,i.quantity,i.storeQuantity,p.name from orders o
                        left join order_items i on i.orderId=o.id
                        left join products p on p.id=i.productId
                        where o.state!=2 and p.zone=:z";

        $storesSql = "select o.storeId,s.name from orders o
                        left join order_items i on i.orderId=o.id
                        left join products p on p.id=i.productId
                        left join stores s on s.id=o.storeId
                        where o.state!=2 and p.zone=:z
                        group by o.storeId order by s.name";

        foreach ($zones as $zone) {
            $p = [];
            $products = Yii::$app->db->createCommand($productSql)
                ->bindValue(":z", $zone['name'], PDO::PARAM_STR)
                ->queryAll();
            if (empty($products))
                continue;

            $qs = Yii::$app->db->createCommand($quantitySql)
                ->bindValue(":z", $zone['name'], PDO::PARAM_STR)
                ->queryAll();

            $stores = Yii::$app->db->createCommand($storesSql)
                ->bindValue(":z", $zone['name'], PDO::PARAM_STR)
                ->queryAll();

            foreach ($products as $product) {
                $p[$product['productId']]['name'] = $product['name'];
                $p[$product['productId']]['unit'] = $product['mainUnit'];
            }

            foreach ($qs as $q) {
                if (!empty($p[$q['productId']][$q['storeId']])) {
                    $p[$q['productId']][$q['storeId']] = $p[$q['productId']][$q['storeId']] + $q['quantity'];
                } else {
                    $p[$q['productId']][$q['storeId']] = $q['quantity'];
                }
            }

            $spreadsheet->createSheet($i);

            $row = 1;
            foreach ($p as $id => $product) {
                if ($row == 1) {
                    $columnCode = Dashboard::getColumn(0);
                    $columnName = "Наименование";
                    $spreadsheet->setActiveSheetIndex($i)
                        ->setCellValue($columnCode . $row, $columnName);

                    $columnCode = Dashboard::getColumn(1);
                    $columnName = "Ед. изм.";
                    $spreadsheet->setActiveSheetIndex($i)
                        ->setCellValue($columnCode . $row, $columnName);
                    $row++;
                }
                $columnCode = Dashboard::getColumn(0);
                $spreadsheet->setActiveSheetIndex($i)
                    ->setCellValue($columnCode . $row, $product['name']);

                $columnCode = Dashboard::getColumn(1);
                $spreadsheet->setActiveSheetIndex($i)
                    ->setCellValue($columnCode . $row, $product['unit']);

                $col = 2;
                foreach ($stores as $store) {
                    $columnCode = Dashboard::getColumn($col);
                    if ($row == 2) {
                        $spreadsheet->setActiveSheetIndex($i)
                            ->setCellValue($columnCode . '1', $store['name']);
                    }
                    $quantity = !empty($p[$id][$store['storeId']]) ? $p[$id][$store['storeId']] : 0;
                    $spreadsheet->setActiveSheetIndex($i)
                        ->setCellValue($columnCode . $row, $quantity);
                    $col++;
                }

                $row++;
            }
            $spreadsheet->setActiveSheetIndex($i)
                ->getStyle("A1:ZZ1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ]
                ]);

            for ($j = 0; $j < $col; $j++) {
                $columnCode = Dashboard::getColumn($j);
                $spreadsheet->getActiveSheet()
                    ->setTitle($zone['name'])
                    ->getColumnDimension($columnCode)->setAutoSize(true);
            }
            $i++;
        }

        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment; filename="Заказы-' . $date . '.xlsx"');
        $writer->save("php://output");
    }

    public function actionStockByProduct()
    {
        $data = OrderItems::getStockOrderByProducts();
        return $this->render('stock-by-product', [
            'data' => $data,
        ]);
    }

    public function actionStockByProductExcel()
    {
        $date = date("Y-m-d");

        $k = 1;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet(0);
        $spreadsheet->setActiveSheetIndex(0)
            ->setTitle('Итого')
            ->setCellValue('A' . $k, "Продукт")
            ->setCellValue('B' . $k, "Ед. Изм.")
            ->setCellValue('C' . $k, "Кол.")
            ->getStyle("A{$k}:C{$k}")->applyFromArray([
                'font' => [
                    'bold' => true,
                ]
            ]);

        $data = OrderItems::getStockOrderByProducts();
        foreach ($data as $row) {
            $k++;
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $k, $row['name'])
                ->setCellValue('B' . $k, $row['mainUnit'])
                ->setCellValue('C' . $k, $row['total'])
                ->getColumnDimension("A")->setAutoSize(true);
        }

        $spreadsheet->createSheet(1);
        $spreadsheet->setActiveSheetIndex(1)
            ->setCellValue('A1', "Продукт")
            ->setCellValue('B1', "Ед. Изм.")
            ->setCellValue('C1', "Кол.")
            ->setTitle('Пустой');

        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment; filename="Заказы-' . $date . '.xlsx"');
        $writer->save("php://output");
    }

    public function actionOrderExcel($id)
    {
        $model = $this->findModel($id);
        $products = Orders::getOrderProducts($model->id);
        $groupedProducts = [];
        uasort($products, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        foreach ($products as $product) {
            if (empty($groupedProducts[$product['groupName']])) {
                $groupedProducts[$product['groupName']] = [
                    'name' => $product['groupName'],
                    'products' => []
                ];
            }
            $groupedProducts[$product['groupName']]['products'][] = $product;
        }
        uasort($groupedProducts, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        $content = $this->renderPartial('order-excel', [
            'model' => $model,
            'groupedProducts' => $groupedProducts,
        ]);

        $file = "Заказ-#{$model->id}.xls";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;');
        header('Content-Disposition: attachment;filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        Yii::$app->response->data = $content;
    }

    //Supplier Methods
    public function actionBuyerOrders()
    {
        $searchModel = new OrderSearch();
//        $searchModel->state = 0;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);

        return $this->render('buyer-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionBuyer($tab = null)
    {
        $date = date("Y-m-d");
        $userId = Yii::$app->user->id;
        $supplierId = User::getSupplierId();

        $orders = Orders::getOrders($date);
        Yii::$app->db->createCommand("update orders set editable=0 where state=0 and editable=1")->execute();
        if (Yii::$app->request->isPost) {
            $orderId = Yii::$app->request->post("orderId");
            $items = Yii::$app->request->post("Items");

            foreach ($items as $key => $value) {
                $oi = OrderItems::findOne(['orderId' => $orderId, 'productId' => $key]);
                $oi->supplierId = $supplierId;
                $oi->purchaseQuantity = $value['purchaseQuantity'];
                $oi->price = $value['price'];
                $oi->save();
            }
            return $this->redirect(['orders/buyer', 'tab' => $orderId]);
        }

        return $this->render('buyer', [
            'date' => $date,
            'orders' => $orders,
            'orderId' => $tab,
        ]);
    }

    public function actionBuyerByProduct()
    {
        $data = OrderItems::getBuyerOrderByProducts();
        return $this->render('buyer-by-product', [
            'data' => $data,
        ]);
    }

    public function actionSupplierExcel()
    {
        $date = date("Y-m-d");

        $orders = Orders::getOrders($date);
        $products = Orders::getOrderSupplierProducts();
        $content = $this->renderPartial('supplier-excel', [
            'orders' => $orders,
            'products' => $products,
        ]);

        $file = "Итого.xls";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;');
        header('Content-Disposition: attachment;filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        Yii::$app->response->data = $content;
    }

    public function actionSupplierExcel2()
    {
        $date = date("Y-m-d");

        $spreadsheet = new Spreadsheet();

        $orders = Orders::getOrders($date);
        $i = 1;

        foreach ($orders as $order) {
            $products = Orders::getOrderSupplier($order['id']);
            if (empty($products))
                continue;

            $k = 2;
            $spreadsheet->createSheet($i);
            $spreadsheet->setActiveSheetIndex($i)
                ->setCellValue('A1', $order['name'])
                ->setCellValue('B1', "Дата: " . date("d.m.Y", strtotime($order['date'])))
                ->mergeCells("B1:C1")
                ->setCellValue('A2', "Продукт")
                ->setCellValue('B2', "Ед. Изм.")
                ->setCellValue('C2', "Кол.")
                ->getStyle("A1:C2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ]
                ]);
            $groupId = "";

            foreach ($products as $product) {
                $k++;
                if ($groupId != $product['parentId']) {
                    $spreadsheet->setActiveSheetIndex($i)
                        ->setCellValue('A' . $k, $product['groupName'])
                        ->mergeCells("A{$k}:C{$k}")
                        ->getStyle("A{$k}:C{$k}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ]
                        ]);
                    $groupId = $product['parentId'];
                    $k++;
                }
                $spreadsheet->setActiveSheetIndex($i)
                    ->setCellValue('A' . $k, $product['name'])
                    ->setCellValue('B' . $k, $product['mainUnit'])
                    ->setCellValue('C' . $k, $product['supplierQuantity'])
                    ->getColumnDimension("A")->setAutoSize(true);
            }
            $title = $order['id'];
            $spreadsheet->getActiveSheet()
                ->setTitle($title)
                ->getColumnDimension("A")->setAutoSize(true);
            $i++;
        }

        $spreadsheet->createSheet($i);
        $spreadsheet->setActiveSheetIndex($i)
            ->setCellValue('A1', "Продукт")
            ->setCellValue('B1', "Ед. Изм.")
            ->setCellValue('C1', "Кол.")
            ->setTitle('Пустой');

        $k = 1;
        $spreadsheet->setActiveSheetIndex(0)
            ->setTitle('Итого')
            ->setCellValue('A' . $k, "Продукт")
            ->setCellValue('B' . $k, "Ед. Изм.")
            ->setCellValue('C' . $k, "Кол.")
            ->getStyle("A{$k}:C{$k}")->applyFromArray([
                'font' => [
                    'bold' => true,
                ]
            ]);

        $data = OrderItems::getBuyerOrderByProducts();
        foreach ($data as $row) {
            $k++;
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $k, $row['name'])
                ->setCellValue('B' . $k, $row['mainUnit'])
                ->setCellValue('C' . $k, $row['total'])
                ->getColumnDimension("A")->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment; filename="Заказы-' . $date . '.xlsx"');
        $writer->save("php://output");
    }

    /**
     * Deletes an existing Settings model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReturn($id)
    {
        $model = $this->findModel($id);
        $model->state = 0;
        $model->save();

        return $this->redirect(['index']);
    }

    /**
     * Deletes an existing Settings model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->delete()) {
            Yii::$app->db->createCommand("delete from order_items where orderId=:o")
                ->bindValue(":o", $model->id, PDO::PARAM_INT)
                ->execute();
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Orders model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Orders the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Orders::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function filterOrdersByDate($start = null, $end = null) {
        if ($start == null) {
            $start = date('d.m.Y');
        }
        if ($end == null) {
            $end = date('d.m.Y');
        }

//        $searchModel = new OrderSearch();
//        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $start, $end);
//
//        return $this->render('courier-payments', [
//            'searchModel' => $searchModel,
//            'dataProvider' => $dataProvider,
//            'start' => $start,
//            'end' => $end
//        ]);
    }
}
