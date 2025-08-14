<?php

namespace app\controllers;

use Yii;
use app\models\OilInventory;
use app\models\OilInventorySearch;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\components\AccessRule;

/**
 * OilInventoryController implements the CRUD actions for OilInventory model.
 */
class OilInventoryController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
                        'actions' => ['filled', 'review', 'approve', 'reject'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                        ]
                    ],
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'print'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                            User::ROLE_MANAGER,
                            User::ROLE_STOCK,
                            User::ROLE_BUYER,
                            User::ROLE_BARMEN,
                            User::ROLE_COOK,
                            User::ROLE_PASTRY,
                        ],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'approve' => ['POST'],
                    'reject' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all OilInventory models for current user's store.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OilInventorySearch();
        
        // Получаем store_id текущего пользователя
        $currentUserStoreId = User::getStoreId();
        
        // Если у пользователя нет store_id, показываем пустой результат
        if (empty($currentUserStoreId)) {
            $searchModel->store_id = 'no-store'; // Несуществующий store_id
        } else {
            $searchModel->store_id = $currentUserStoreId;
        }
        
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single OilInventory model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись принадлежит магазину текущего пользователя
        $this->checkStoreAccess($model);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new OilInventory model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new OilInventory();
        
        // Автоматически устанавливаем store_id текущего пользователя
        $currentUserStoreId = User::getStoreId();
        if (empty($currentUserStoreId)) {
            Yii::$app->session->setFlash('error', 'У вас не назначен магазин. Обратитесь к администратору.');
            return $this->redirect(['index']);
        }
        
        $model->store_id = $currentUserStoreId;
        
        // Устанавливаем статус по умолчанию
        $model->status = OilInventory::STATUS_NEW;
        
        // Проверяем, нет ли уже записи за сегодня
        $today = date('Y-m-d');
        $todayRecord = OilInventory::find()
            ->where(['store_id' => $currentUserStoreId])
            ->andWhere(['>=', 'DATE(created_at)', $today])
            ->one();
            
        if ($todayRecord) {
            Yii::$app->session->setFlash('error', 'Запись за сегодня уже существует. Вы можете только редактировать существующую запись.');
            return $this->redirect(['index']);
        }
        
        // Проверяем, есть ли записи со статусом, отличным от "принят", за предыдущие дни
        $incompleteRecord = OilInventory::find()
            ->where(['store_id' => null /*$currentUserStoreId*/])
            ->andWhere(['!=', 'status', OilInventory::STATUS_FILLED])
            ->andWhere(['<', 'DATE(created_at)', $today])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
        
        // Получаем остаток на конец дня из записи предыдущего дня
        $previousRecord = OilInventory::find()
            ->where(['store_id' => $currentUserStoreId])
            ->andWhere(['<', 'DATE(created_at)', $today])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
            
        if ($previousRecord) {
            $model->opening_balance = $previousRecord->closing_balance;
        } else {
            $model->opening_balance = 0; // Если это первая запись
        }

        if ($model->load(Yii::$app->request->post())) {
            $model->status = OilInventory::STATUS_FILLED;
            $model->closing_balance = $model->new_oil + $model->apparatus;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Запись успешно создана.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при создании записи.');
            }
        }

        return $this->render('create', [
            'model' => $model,
            'incompleteRecord' => $incompleteRecord,
        ]);
    }

    /**
     * Updates an existing OilInventory model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись принадлежит магазину текущего пользователя
        $this->checkStoreAccess($model);
        
        // Проверяем, что запись не имеет статус "Принят"
        if ($model->status === OilInventory::STATUS_ACCEPTED) {
            Yii::$app->session->setFlash('error', 'Невозможно редактировать запись со статусом "Принят".');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Запись успешно обновлена.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing OilInventory model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись принадлежит магазину текущего пользователя
        $this->checkStoreAccess($model);
        
        // Проверяем, что запись не имеет статус "Принят"
        if ($model->status === OilInventory::STATUS_ACCEPTED) {
            Yii::$app->session->setFlash('error', 'Невозможно удалить запись со статусом "Принят".');
            return $this->redirect(['index']);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Запись успешно удалена.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the OilInventory model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OilInventory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OilInventory::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не существует.');
    }

    /**
     * Проверяет, что пользователь имеет доступ к записи (принадлежит его магазину)
     * @param OilInventory $model
     * @throws NotFoundHttpException
     */
    protected function checkStoreAccess($model)
    {
        $currentUserStoreId = User::getStoreId();
        
        if (empty($currentUserStoreId) || $model->store_id !== $currentUserStoreId) {
            throw new NotFoundHttpException('У вас нет доступа к этой записи.');
        }
    }

    /**
     * Lists all OilInventory models with "filled" status for review.
     * @return mixed
     */
    public function actionFilled()
    {
        $searchModel = new OilInventorySearch();
        
        // Фильтруем только записи со статусом "заполнен"
        $searchModel->status = OilInventory::STATUS_FILLED;
        
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        // Добавляем дополнительный фильтр для записей с возвратом больше нуля
        $dataProvider->query->andWhere(['>', 'return_amount_kg', 0]);

        return $this->render('filled', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single OilInventory model for review with approve/reject actions.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReview($id)
    {
        $model = $this->findModel($id);
        

        // Проверяем, что запись имеет статус "заполнен" и может быть рассмотрена
        if ($model->status !== OilInventory::STATUS_FILLED) {
            Yii::$app->session->setFlash('error', 'Только записи со статусом "Заполнен" могут быть рассмотрены.');
            return $this->redirect(['filled']);
        }

        return $this->render('review', [
            'model' => $model,
        ]);
    }

    /**
     * Approves an OilInventory record.
     * @param integer $id
     * @return mixed
     */
    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись имеет статус "заполнен"
        if ($model->status !== OilInventory::STATUS_FILLED) {
            Yii::$app->session->setFlash('error', 'Только записи со статусом "Заполнен" могут быть приняты.');
            return $this->redirect(['filled']);
        }
        
        $model->status = OilInventory::STATUS_ACCEPTED;
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Запись успешно принята.');
        } else {
	echo '<pre>'; print_r($model->getErrors()); echo '</pre>'; die();
            Yii::$app->session->setFlash('error', 'Ошибка при принятии записи.');
        }

        return $this->redirect(['filled']);
    }

    /**
     * Rejects an OilInventory record.
     * @param integer $id
     * @return mixed
     */
    public function actionReject($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись имеет статус "заполнен"
        if ($model->status !== OilInventory::STATUS_FILLED) {
            Yii::$app->session->setFlash('error', 'Только записи со статусом "Заполнен" могут быть отклонены.');
            return $this->redirect(['filled']);
        }
        
        $model->status = OilInventory::STATUS_REJECTED;
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Запись отклонена.');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при отклонении записи.');
        }

        return $this->redirect(['filled']);
    }

    /**
     * Обновляет статус записи
     * @param integer $id
     * @param string $status
     * @return mixed
     */
    public function actionUpdateStatus($id, $status)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись принадлежит магазину текущего пользователя
        $this->checkStoreAccess($model);
        
        // Проверяем, что статус валидный
        $validStatuses = [
            OilInventory::STATUS_NEW,
            OilInventory::STATUS_FILLED,
            OilInventory::STATUS_REJECTED,
            OilInventory::STATUS_ACCEPTED
        ];
        
        if (!in_array($status, $validStatuses)) {
            throw new NotFoundHttpException('Неверный статус.');
        }
        
        $model->status = $status;
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Статус успешно обновлен.');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при обновлении статуса.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Prints a single OilInventory model in a receipt format.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionPrint($id)
    {
        $model = $this->findModel($id);
        
        // Проверяем, что запись принадлежит магазину текущего пользователя
        $this->checkStoreAccess($model);
        
        // Используем специальный layout для печати
        $this->layout = 'print';

        return $this->render('_print', [
            'model' => $model,
        ]);
    }
} 
