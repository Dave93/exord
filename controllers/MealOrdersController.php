<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Dishes;
use app\models\MealOrderItems;
use app\models\MealOrders;
use app\models\MealOrderSearch;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
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
                        'actions' => ['index', 'view', 'delete', 'close', 'return-back', 'return-to-new', 'send', 'restore-item'],
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
                        ],
                    ],
                    [
                        'actions' => ['index', 'view', 'send', 'close'],
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

    public function actionIndex($start = null, $end = null)
    {
        if ($start == null) {
            $start = date('Y-m-d');
        }
        if ($end == null) {
            $end = date('Y-m-d');
        }

        $searchModel = new MealOrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false, $start, $end);

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
        $model = new MealOrders();
        $model->date = date("Y-m-d");
        $model->storeId = Yii::$app->user->identity->store_id;
        $model->userId = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $items = Yii::$app->request->post("Items");
            $model->addDate = date("Y-m-d H:i:s");
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
        if (in_array(Yii::$app->user->identity->role, [User::ROLE_BARMEN, User::ROLE_COOK, User::ROLE_PASTRY, User::ROLE_MANAGER])) {
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
