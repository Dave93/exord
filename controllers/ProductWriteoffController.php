<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\ProductWriteoff;
use app\models\ProductWriteoffItem;
use app\models\ProductWriteoffSearch;
use app\models\Products;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * ProductWriteoffController implements the CRUD actions for ProductWriteoff model.
 */
class ProductWriteoffController extends Controller
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
                        'actions' => ['index', 'create', 'update', 'view'],
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
                        'actions' => ['approve', 'delete'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                            User::ROLE_OFFICE,
                        ],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'approve' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Список списаний для текущего пользователя
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProductWriteoffSearch();

        // Если не админ/офис, показываем только списания своего магазина
        if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) {
            $searchModel->store_id = Yii::$app->user->identity->store_id;
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->sort = ['defaultOrder' => ['id' => SORT_DESC]];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Просмотр списания
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Создание нового списания
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ProductWriteoff();
        $model->store_id = Yii::$app->user->identity->store_id;
        $model->created_by = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post['items']) && is_array($post['items'])) {
                $transaction = Yii::$app->db->beginTransaction();

                try {
                    $model->comment = $post['comment'] ?? null;
                    $model->status = ProductWriteoff::STATUS_NEW;

                    if (!$model->save()) {
                        throw new \Exception('Ошибка сохранения списания');
                    }

                    $hasItems = false;
                    foreach ($post['items'] as $productId => $count) {
                        if (empty($count) || $count <= 0) {
                            continue;
                        }

                        $item = new ProductWriteoffItem();
                        $item->writeoff_id = $model->id;
                        $item->product_id = $productId;
                        $item->count = $count;

                        if (!$item->save()) {
                            throw new \Exception('Ошибка сохранения позиции списания');
                        }

                        $hasItems = true;
                    }

                    if ($hasItems) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Списание успешно создано');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Не указано ни одного продукта для списания');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Ошибка при создании списания: ' . $e->getMessage());
                }
            }
        }

        // Получаем список продуктов по категориям
        $folders = Products::getProductParents(Yii::$app->user->id);

        return $this->render('create', [
            'model' => $model,
            'folders' => $folders,
        ]);
    }

    /**
     * Обновление списания
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (!$model->canEdit()) {
            Yii::$app->session->setFlash('error', 'Нельзя редактировать утвержденное списание');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post['items']) && is_array($post['items'])) {
                $transaction = Yii::$app->db->beginTransaction();

                try {
                    $model->comment = $post['comment'] ?? null;

                    if (!$model->save()) {
                        throw new \Exception('Ошибка обновления списания');
                    }

                    // Удаляем старые позиции
                    ProductWriteoffItem::deleteAll(['writeoff_id' => $model->id]);

                    // Добавляем новые позиции
                    $hasItems = false;
                    foreach ($post['items'] as $productId => $count) {
                        if (empty($count) || $count <= 0) {
                            continue;
                        }

                        $item = new ProductWriteoffItem();
                        $item->writeoff_id = $model->id;
                        $item->product_id = $productId;
                        $item->count = $count;

                        if (!$item->save()) {
                            throw new \Exception('Ошибка сохранения позиции списания');
                        }

                        $hasItems = true;
                    }

                    if ($hasItems) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Списание обновлено');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Не указано ни одного продукта для списания');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Ошибка при обновлении списания: ' . $e->getMessage());
                }
            }
        }

        // Получаем список продуктов по категориям
        $folders = Products::getProductParents(Yii::$app->user->id);

        return $this->render('update', [
            'model' => $model,
            'folders' => $folders,
        ]);
    }

    /**
     * Утверждение списания
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionApprove($id)
    {
        $model = $this->findModel($id);

        $approvedCounts = Yii::$app->request->post('approved_counts');

        if ($model->approve($approvedCounts)) {
            Yii::$app->session->setFlash('success', 'Списание утверждено');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при утверждении списания');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Удаление списания
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->status === ProductWriteoff::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить утвержденное списание');
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Списание удалено');
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the ProductWriteoff model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ProductWriteoff the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ProductWriteoff::findOne($id)) !== null) {
            // Проверяем права доступа
            if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) {
                if ($model->store_id != Yii::$app->user->identity->store_id) {
                    throw new NotFoundHttpException('Нет доступа к этому списанию.');
                }
            }

            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }
}
