<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Dishes;
use app\models\MealOrderItems;
use app\models\MealOrders;
use app\models\MealOrderSearch;
use app\models\TelegramBot;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class MealOrdersController extends Controller
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
                        'actions' => ['index', 'stock', 'view', 'delete', 'close', 'return-back', 'return-to-new', 'send', 'restore-item'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                            User::ROLE_OFFICE,
                        ],
                    ],
                    [
                        'actions' => ['customer-orders', 'create', 'update', 'view'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_COOK,
                            User::ROLE_BARMEN,
                            User::ROLE_PASTRY,
                            User::ROLE_MANAGER,
                            User::ROLE_ADMIN,
                            User::ROLE_DISH_COOK,
                        ],
                    ],
                    [
                        'actions' => ['index', 'stock', 'view'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_DISH_COOK,
                        ],
                    ],
                    [
                        'actions' => ['index', 'stock', 'view', 'send', 'close'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_STOCK,
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

    public function actionStock($tab = null)
    {
        $date = date("Y-m-d");

        $query = new Query();
        $orders = $query->select("stores.id as storeId, stores.name, meal_orders.id, meal_orders.date, meal_orders.storeId, meal_orders.comment, meal_orders.addDate, meal_orders.state, meal_orders.userId")
            ->from("meal_orders")
            ->leftJoin("stores", "stores.id=meal_orders.storeId")
            ->where(['and',
                'meal_orders.deleted_at is null',
                ['or',
                    ['meal_orders.date' => $date],
                    ['meal_orders.state' => 0],
                ],
            ])
            ->orderBy("stores.name")
            ->all();

        if (Yii::$app->request->isPost) {
            $orderId = Yii::$app->request->post("orderId");
            $isSend = Yii::$app->request->post("send");
            $isDelete = Yii::$app->request->post("delete");
            $isClose = Yii::$app->request->post("close");
            $comment = Yii::$app->request->post("comment");

            $model = MealOrders::findOne(['id' => $orderId]);

            if ($isDelete == 'Y' && Yii::$app->user->identity->role == User::ROLE_ADMIN) {
                $model->deleted_at = date("Y-m-d H:i:s");
                $model->deleted_by = Yii::$app->user->id;
                $model->save(false);
                return $this->redirect(['meal-orders/stock']);
            }

            if ($comment !== null) {
                $model->comment = $comment;
            }

            if ($isSend == 'Y') {
                $model->state = 1;
                $model->is_locked = 1;
            }

            if ($isClose == 'Y') {
                $model->state = 2;
            }

            $model->save(false);

            return $this->redirect(['meal-orders/stock', 'tab' => $orderId]);
        }

        return $this->render('stock', [
            'date' => $date,
            'orders' => $orders,
            'orderId' => $tab,
        ]);
    }

    public function actionIndex($start = null, $end = null)
    {
        if ($start == null) {
            $start = date('Y-m-d');
        }
        if ($end == null) {
            $end = date('Y-m-d');
        }

        $searchModel = new MealOrderSearch();
        $state = null;
        if (Yii::$app->user->identity->role == User::ROLE_DISH_COOK) {
            $state = 1;
        }
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false, $start, $end, $state);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function actionCustomerOrders()
    {
        $searchModel = new MealOrderSearch();
        $searchModel->userId = Yii::$app->user->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);
        $dataProvider->sort = ['defaultOrder' => ['id' => SORT_DESC]];

        return $this->render('customer-orders', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $tz = new \DateTimeZone('Asia/Tashkent');
        $now = new \DateTime('now', $tz);
        $currentHour = (int)$now->format('H');

        // Ограничение по времени: заказывать можно только с 08:00 до 17:00
        if ($currentHour < 8 || $currentHour >= 17) {
            Yii::$app->session->setFlash('error', 'Заказ блюд доступен только с 08:00 до 17:00.');
            return $this->redirect(['customer-orders']);
        }

        // Проверка: нельзя заказывать дважды в один день
        $today = $now->format('Y-m-d');
        $existingOrder = MealOrders::find()
            ->where(['userId' => Yii::$app->user->id, 'date' => $today])
            ->andWhere(['deleted_at' => null])
            ->one();

        if ($existingOrder !== null) {
            Yii::$app->session->setFlash('error', 'Вы уже создали заказ блюд на сегодня (заказ #' . $existingOrder->id . ').');
            return $this->redirect(['customer-orders']);
        }

        $model = new MealOrders();
        $model->date = $today;
        $model->storeId = Yii::$app->user->identity->store_id;
        $model->userId = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            // Повторная проверка на дубликат перед сохранением (защита от двух вкладок)
            $duplicateOrder = MealOrders::find()
                ->where(['userId' => Yii::$app->user->id, 'date' => $today])
                ->andWhere(['deleted_at' => null])
                ->one();
            if ($duplicateOrder !== null) {
                Yii::$app->session->setFlash('error', 'Вы уже создали заказ блюд на сегодня (заказ #' . $duplicateOrder->id . ').');
                return $this->redirect(['customer-orders']);
            }

            $model->load(Yii::$app->request->post());
            $items = Yii::$app->request->post("Items");
            $model->addDate = $now->format("Y-m-d H:i:s");
            $model->state = 0;

            if (!empty($items) && $model->save()) {
                foreach ($items as $dishId => $quantity) {
                    if (empty($quantity) || $quantity <= 0) {
                        continue;
                    }
                    $item = new MealOrderItems();
                    $item->mealOrderId = $model->id;
                    $item->dishId = $dishId;
                    $item->quantity = $quantity;
                    $item->userId = Yii::$app->user->id;
                    $item->save();
                }

                // Отправка уведомления в Telegram (PDF + текст)
                $d = date('d.m.Y', strtotime($model->date));
                $text = "Поступил новый заказ блюд: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата: {$d}";

                $content = $this->renderPartial('preview-invoice', [
                    'model' => $model
                ]);

                $webroot = Yii::getAlias('@webroot');
                $pdf = new \kartik\mpdf\Pdf([
                    'mode' => \kartik\mpdf\Pdf::MODE_UTF8,
                    'content' => $content,
                ]);
                $filePath = '/uploads/meal_' . sha1($model->id) . '.pdf';
                $pdf->output($content, $webroot . $filePath, 'F');

                try {
                    $bot = new TelegramBot();
                    $message = $bot->sendDocument(-1003773200982, 'https://les.iiko.uz' . $filePath);
                    $bot->sendMessage(-1003773200982, $text, 'HTML', false, $message['result']['message_id']);
                } catch (\Exception $e) {
                    Yii::error('Ошибка отправки в Telegram: ' . $e->getMessage(), 'telegram');
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $dishes = Dishes::getActiveDishes();

        return $this->render('create', [
            'model' => $model,
            'dishes' => $dishes,
        ]);
    }

    public function actionUpdate($id)
    {
        if (in_array(Yii::$app->user->identity->role, [User::ROLE_BARMEN, User::ROLE_COOK, User::ROLE_PASTRY, User::ROLE_MANAGER, User::ROLE_DISH_COOK])) {
            $model = MealOrders::findOne(['id' => $id, 'userId' => Yii::$app->user->id]);
        } else {
            $model = MealOrders::findOne($id);
        }

        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        if ($model->state != 0 || $model->is_locked) {
            Yii::$app->session->setFlash('error', 'Этот заказ нельзя редактировать.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Ограничение по времени: редактировать можно только с 08:00 до 17:00
        $tz = new \DateTimeZone('Asia/Tashkent');
        $now = new \DateTime('now', $tz);
        $currentHour = (int)$now->format('H');
        if ($currentHour < 8 || $currentHour >= 17) {
            Yii::$app->session->setFlash('error', 'Редактирование заказа доступно только с 08:00 до 17:00.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Получить существующие позиции для отображения в форме
        $existingItems = [];
        foreach ($model->items as $item) {
            $existingItems[$item->dishId] = $item->quantity;
        }

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $items = Yii::$app->request->post("Items");

            if ($model->save()) {
                // Soft delete старых позиций
                $oldItems = MealOrderItems::findWithDeleted()
                    ->where(['mealOrderId' => $model->id])
                    ->all();
                foreach ($oldItems as $oldItem) {
                    $oldItem->deleted_at = date('Y-m-d H:i:s');
                    $oldItem->deleted_by = Yii::$app->user->id;
                    $oldItem->save(false);
                }

                // Создать новые позиции
                if (!empty($items)) {
                    foreach ($items as $dishId => $quantity) {
                        if (empty($quantity) || $quantity <= 0) {
                            continue;
                        }
                        $item = new MealOrderItems();
                        $item->mealOrderId = $model->id;
                        $item->dishId = $dishId;
                        $item->quantity = $quantity;
                        $item->userId = Yii::$app->user->id;
                        $item->save();
                    }
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $dishes = Dishes::getActiveDishes();

        return $this->render('create', [
            'model' => $model,
            'dishes' => $dishes,
            'existingItems' => $existingItems,
        ]);
    }

    public function actionView($id, $showDeleted = 0)
    {
        if (in_array(Yii::$app->user->identity->role, [User::ROLE_BARMEN, User::ROLE_COOK, User::ROLE_PASTRY])) {
            $model = MealOrders::findOne(['id' => $id, 'userId' => Yii::$app->user->id]);
        } else {
            $model = MealOrders::findOne($id);
        }

        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        if ($showDeleted && in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) {
            $query = MealOrderItems::findWithDeleted()->where(['mealOrderId' => $model->id]);
        } else {
            $query = MealOrderItems::find()->andWhere(['mealOrderId' => $model->id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        return $this->render('view', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'showDeleted' => $showDeleted,
        ]);
    }

    public function actionSend($id)
    {
        $model = MealOrders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        $model->state = 1;
        $model->is_locked = 1;
        $model->save(false);

        return $this->redirect(['index']);
    }

    public function actionClose($id)
    {
        $model = MealOrders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        $model->state = 2;
        $model->save(false);

        $d = date('d.m.Y H:i');
        $text = "Заказ блюд закрыт: <b>#{$model->id}</b>\nЗаказчик: {$model->user->username}\nДата: {$d}";
        try {
            $bot = new TelegramBot();
            $bot->sendMessage(-1003773200982, $text, 'HTML');
        } catch (\Exception $e) {
            Yii::error('Ошибка отправки в Telegram: ' . $e->getMessage(), 'telegram');
        }

        Yii::$app->session->setFlash('success', 'Заказ блюд #' . $model->id . ' закрыт.');
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $model = MealOrders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        $model->deleted_at = date('Y-m-d H:i:s');
        $model->deleted_by = Yii::$app->user->id;
        $model->save(false);

        return $this->redirect(['index']);
    }

    public function actionReturnBack($id)
    {
        $model = MealOrders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }
        $model->deleted_at = null;
        $model->deleted_by = null;
        $model->save(false);

        return $this->redirect(['index']);
    }

    public function actionReturnToNew($id)
    {
        $model = MealOrders::findOne($id);
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }
        if ($model->state == 1) {
            $model->state = 0;
            $model->is_locked = 0;
            $model->save(false);
        }
        return $this->redirect(['index']);
    }

    public function actionRestoreItem($mealOrderId, $dishId)
    {
        $item = MealOrderItems::findWithDeleted()
            ->where(['mealOrderId' => $mealOrderId, 'dishId' => $dishId])
            ->one();

        if ($item !== null) {
            $item->restore();
            Yii::$app->session->setFlash('success', 'Позиция восстановлена.');
        }

        return $this->redirect(['view', 'id' => $mealOrderId, 'showDeleted' => 1]);
    }
}
