<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\StoreTransfer;
use app\models\StoreTransferItem;
use app\models\StoreTransferSearch;
use app\models\Products;
use app\models\Stores;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\Json;

/**
 * StoreTransferController implements the CRUD actions for StoreTransfer model.
 */
class StoreTransferController extends Controller
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
                        'actions' => ['index', 'create', 'update', 'view', 'cancel', 'incoming', 'process-incoming', 'confirm-transfer'],
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
                        'actions' => ['admin-index', 'set-in-progress', 'approve-items', 'mark-transferred', 'delete', 'admin-approve', 'final-approve'],
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
                    'cancel' => ['POST'],
                    'set-in-progress' => ['POST'],
                    'approve-items' => ['POST'],
                    'mark-transferred' => ['POST'],
                    'confirm-transfer' => ['POST'],
                    'final-approve' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Список заявок на перемещение для текущего пользователя
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new StoreTransferSearch();

        // Если не админ/офис, показываем только заявки своего магазина
        if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) {
            $searchModel->request_store_id = Yii::$app->user->identity->store_id;
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Список заявок для администраторов с фильтрацией
     * @return mixed
     */
    public function actionAdminIndex()
    {
        $searchModel = new StoreTransferSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('admin-index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Список входящих заявок (где текущий филиал - источник)
     * @return mixed
     */
    public function actionIncoming()
    {
        $currentStoreId = Yii::$app->user->identity->store_id;

        // Находим все заявки, где текущий филиал является источником
        $query = StoreTransfer::find()
            ->joinWith('items')
            ->where([
                'store_transfer_items.source_store_id' => $currentStoreId,
                'store_transfers.status' => [StoreTransfer::STATUS_NEW, StoreTransfer::STATUS_IN_PROGRESS]
            ])
            ->groupBy('store_transfers.id')
            ->orderBy(['store_transfers.created_at' => SORT_DESC]);

        $transfers = $query->all();

        return $this->render('incoming', [
            'transfers' => $transfers,
        ]);
    }

    /**
     * Просмотр и обработка входящей заявки
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionProcessIncoming($id)
    {
        $model = $this->findModelForIncoming($id);
        $currentStoreId = Yii::$app->user->identity->store_id;

        // Фильтруем только позиции для текущего филиала
        $items = StoreTransferItem::find()
            ->where([
                'transfer_id' => $model->id,
                'source_store_id' => $currentStoreId,
            ])
            ->all();

        if (empty($items)) {
            Yii::$app->session->setFlash('error', 'Нет позиций для вашего филиала в этой заявке');
            return $this->redirect(['incoming']);
        }

        return $this->render('process-incoming', [
            'model' => $model,
            'items' => $items,
        ]);
    }

    /**
     * Подтверждение передачи товаров
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionConfirmTransfer($id)
    {
        $model = $this->findModelForIncoming($id);
        $currentStoreId = Yii::$app->user->identity->store_id;
        $transferredQuantities = Yii::$app->request->post('transferred_quantities', []);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $hasUpdates = false;

            foreach ($transferredQuantities as $itemId => $quantity) {
                $item = StoreTransferItem::findOne([
                    'id' => $itemId,
                    'transfer_id' => $model->id,
                    'source_store_id' => $currentStoreId,
                ]);

                if ($item && $item->item_status === StoreTransferItem::STATUS_PENDING) {
                    if ($quantity > 0) {
                        // Устанавливаем переданное количество и меняем статус
                        $item->transferred_quantity = $quantity;
                        $item->item_status = StoreTransferItem::STATUS_TRANSFERRED;
                        $hasUpdates = true;
                    } else {
                        // Если количество 0, считаем что отклонили
                        $item->item_status = StoreTransferItem::STATUS_REJECTED;
                        $hasUpdates = true;
                    }
                    $item->save(false);
                }
            }

            if ($hasUpdates) {
                // Переводим заявку в статус "В работе", если она была новой
                // Окончательное завершение будет только после утверждения админом
                if ($model->status === StoreTransfer::STATUS_NEW) {
                    $model->status = StoreTransfer::STATUS_IN_PROGRESS;
                    $model->save(false);
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Передача подтверждена. Заявка ожидает утверждения администратором.');
            } else {
                $transaction->rollBack();
                Yii::$app->session->setFlash('warning', 'Нет изменений для сохранения');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Ошибка при подтверждении передачи: ' . $e->getMessage());
        }

        return $this->redirect(['incoming']);
    }

    /**
     * Админский интерфейс для утверждения окончательных количеств
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionAdminApprove($id)
    {
        $model = StoreTransfer::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Заявка не найдена.');
        }

        // Группируем позиции по филиалам-источникам
        $itemsByStore = [];
        foreach ($model->items as $item) {
            if (!isset($itemsByStore[$item->source_store_id])) {
                $itemsByStore[$item->source_store_id] = [
                    'store' => $item->sourceStore,
                    'items' => [],
                ];
            }
            $itemsByStore[$item->source_store_id]['items'][] = $item;
        }

        return $this->render('admin-approve', [
            'model' => $model,
            'itemsByStore' => $itemsByStore,
        ]);
    }

    /**
     * Окончательное утверждение и завершение заявки админом
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionFinalApprove($id)
    {
        $model = StoreTransfer::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Заявка не найдена.');
        }

        $approvedQuantities = Yii::$app->request->post('approved_quantities', []);
        $itemStatuses = Yii::$app->request->post('item_statuses', []);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $hasApproved = false;

            foreach ($model->items as $item) {
                if (isset($itemStatuses[$item->id])) {
                    $status = $itemStatuses[$item->id];
                    $approvedQty = isset($approvedQuantities[$item->id]) ? $approvedQuantities[$item->id] : null;

                    // Пропускаем уже утвержденные позиции
                    if ($item->item_status === StoreTransferItem::STATUS_APPROVED) {
                        $hasApproved = true;
                        continue;
                    }

                    if ($status === 'approved' && $approvedQty !== null && $approvedQty > 0) {
                        // Утверждаем с указанным количеством
                        $item->approved_quantity = $approvedQty;
                        $item->item_status = StoreTransferItem::STATUS_APPROVED;
                        $hasApproved = true;
                        $item->save(false);
                    } elseif ($status === 'rejected') {
                        // Отклоняем
                        $item->approved_quantity = 0;
                        $item->item_status = StoreTransferItem::STATUS_REJECTED;
                        $item->save(false);
                    }
                    // Если статус 'pending', не меняем ничего
                }
            }

            // Обновляем статус заявки
            // Проверяем, все ли позиции обработаны (approved или rejected)
            $allProcessed = true;
            $hasApprovedItems = false;

            foreach ($model->items as $item) {
                if ($item->item_status === StoreTransferItem::STATUS_PENDING ||
                    $item->item_status === StoreTransferItem::STATUS_TRANSFERRED) {
                    $allProcessed = false;
                    break;
                }
                if ($item->item_status === StoreTransferItem::STATUS_APPROVED) {
                    $hasApprovedItems = true;
                }
            }

            if ($allProcessed) {
                // Все позиции обработаны
                if ($hasApprovedItems) {
                    $model->status = StoreTransfer::STATUS_COMPLETED;
                } else {
                    // Все отклонены
                    $model->status = StoreTransfer::STATUS_CANCELLED;
                }
            } else {
                // Не все обработаны, остаемся в работе
                $model->status = StoreTransfer::STATUS_IN_PROGRESS;
            }

            $model->save(false);
            $transaction->commit();

            Yii::$app->session->setFlash('success', 'Заявка успешно утверждена');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Ошибка при утверждении заявки: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Просмотр заявки на перемещение
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        // Группируем позиции по филиалам-источникам
        $itemsByStore = [];
        foreach ($model->items as $item) {
            if (!isset($itemsByStore[$item->source_store_id])) {
                $itemsByStore[$item->source_store_id] = [
                    'store' => $item->sourceStore,
                    'items' => [],
                ];
            }
            $itemsByStore[$item->source_store_id]['items'][] = $item;
        }

        return $this->render('view', [
            'model' => $model,
            'itemsByStore' => $itemsByStore,
        ]);
    }

    /**
     * Создание новой заявки на перемещение
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new StoreTransfer();
        $model->request_store_id = Yii::$app->user->identity->store_id;
        $model->created_by = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post['transfers']) && is_array($post['transfers'])) {
                $transaction = Yii::$app->db->beginTransaction();

                try {
                    $model->comment = $post['comment'] ?? null;
                    $model->status = StoreTransfer::STATUS_NEW;

                    if (!$model->save()) {
                        throw new \Exception('Ошибка сохранения заявки: ' . Json::encode($model->errors));
                    }

                    $hasItems = false;
                    // Обрабатываем позиции по филиалам
                    foreach ($post['transfers'] as $sourceStoreId => $products) {
                        if (!is_array($products)) {
                            continue;
                        }

                        foreach ($products as $productId => $quantity) {
                            if (empty($quantity) || $quantity <= 0) {
                                continue;
                            }

                            $item = new StoreTransferItem();
                            $item->transfer_id = $model->id;
                            $item->source_store_id = $sourceStoreId;
                            $item->product_id = $productId;
                            $item->requested_quantity = $quantity;
                            $item->item_status = StoreTransferItem::STATUS_PENDING;

                            if (!$item->save()) {
                                throw new \Exception('Ошибка сохранения позиции: ' . Json::encode($item->errors));
                            }

                            $hasItems = true;
                        }
                    }

                    if ($hasItems) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Заявка на перемещение успешно создана');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Не указано ни одного продукта для перемещения');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Ошибка при создании заявки: ' . $e->getMessage());
                }
            }
        }

        // Получаем список филиалов (исключая текущий)
        $stores = Stores::find()
            ->where(['<>', 'id', $model->request_store_id])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        // Получаем список продуктов по категориям
        $folders = Products::getProductParents(Yii::$app->user->id);

        return $this->render('create', [
            'model' => $model,
            'stores' => $stores,
            'folders' => $folders,
        ]);
    }

    /**
     * Обновление заявки на перемещение
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (!$model->canEdit()) {
            Yii::$app->session->setFlash('error', 'Нельзя редактировать заявку в текущем статусе');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post['transfers']) && is_array($post['transfers'])) {
                $transaction = Yii::$app->db->beginTransaction();

                try {
                    $model->comment = $post['comment'] ?? null;

                    if (!$model->save()) {
                        throw new \Exception('Ошибка обновления заявки');
                    }

                    // Удаляем старые позиции
                    StoreTransferItem::deleteAll(['transfer_id' => $model->id]);

                    // Добавляем новые позиции
                    $hasItems = false;
                    foreach ($post['transfers'] as $sourceStoreId => $products) {
                        if (!is_array($products)) {
                            continue;
                        }

                        foreach ($products as $productId => $quantity) {
                            if (empty($quantity) || $quantity <= 0) {
                                continue;
                            }

                            $item = new StoreTransferItem();
                            $item->transfer_id = $model->id;
                            $item->source_store_id = $sourceStoreId;
                            $item->product_id = $productId;
                            $item->requested_quantity = $quantity;
                            $item->item_status = StoreTransferItem::STATUS_PENDING;

                            if (!$item->save()) {
                                throw new \Exception('Ошибка сохранения позиции');
                            }

                            $hasItems = true;
                        }
                    }

                    if ($hasItems) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Заявка обновлена');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Не указано ни одного продукта для перемещения');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Ошибка при обновлении заявки: ' . $e->getMessage());
                }
            }
        }

        // Получаем список филиалов (исключая текущий)
        $stores = Stores::find()
            ->where(['<>', 'id', $model->request_store_id])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        // Получаем список продуктов по категориям
        $folders = Products::getProductParents(Yii::$app->user->id);

        return $this->render('update', [
            'model' => $model,
            'stores' => $stores,
            'folders' => $folders,
        ]);
    }

    /**
     * Отмена заявки
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionCancel($id)
    {
        $model = $this->findModel($id);

        if ($model->cancel()) {
            Yii::$app->session->setFlash('success', 'Заявка отменена');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при отмене заявки');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Перевести заявку в работу
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionSetInProgress($id)
    {
        $model = $this->findModel($id);

        if ($model->setInProgress()) {
            Yii::$app->session->setFlash('success', 'Заявка взята в работу');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при изменении статуса заявки');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Утверждение позиций заявки
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionApproveItems($id)
    {
        $model = $this->findModel($id);
        $approvedQuantities = Yii::$app->request->post('approved_quantities', []);
        $itemStatuses = Yii::$app->request->post('item_statuses', []);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($model->items as $item) {
                if (isset($itemStatuses[$item->id])) {
                    if ($itemStatuses[$item->id] === 'approved') {
                        $quantity = isset($approvedQuantities[$item->id]) ? $approvedQuantities[$item->id] : null;
                        $item->approve($quantity);
                    } elseif ($itemStatuses[$item->id] === 'rejected') {
                        $item->reject();
                    }
                }
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Позиции заявки обработаны');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Ошибка при обработке позиций: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Отметить позиции как переданные
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionMarkTransferred($id)
    {
        $model = $this->findModel($id);
        $transferredQuantities = Yii::$app->request->post('transferred_quantities', []);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $allTransferred = true;
            foreach ($model->items as $item) {
                if ($item->item_status === StoreTransferItem::STATUS_APPROVED) {
                    $quantity = isset($transferredQuantities[$item->id]) ? $transferredQuantities[$item->id] : null;
                    $item->markTransferred($quantity);
                }

                if ($item->item_status !== StoreTransferItem::STATUS_TRANSFERRED && $item->item_status !== StoreTransferItem::STATUS_REJECTED) {
                    $allTransferred = false;
                }
            }

            // Если все позиции переданы или отклонены, завершаем заявку
            if ($allTransferred) {
                $model->complete();
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Позиции отмечены как переданные');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Ошибка при обновлении статуса: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Удаление заявки
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (!$model->canEdit()) {
            Yii::$app->session->setFlash('error', 'Нельзя удалить заявку в текущем статусе');
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Заявка удалена');
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the StoreTransfer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return StoreTransfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = StoreTransfer::findOne($id)) !== null) {
            // Проверяем права доступа
            if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) {
                if ($model->request_store_id != Yii::$app->user->identity->store_id) {
                    throw new NotFoundHttpException('Нет доступа к этой заявке.');
                }
            }

            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }

    /**
     * Находит модель StoreTransfer для входящих заявок
     * Проверяет, что текущий филиал является источником для этой заявки
     * @param integer $id
     * @return StoreTransfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModelForIncoming($id)
    {
        if (($model = StoreTransfer::findOne($id)) !== null) {
            $currentStoreId = Yii::$app->user->identity->store_id;

            // Проверяем, есть ли позиции для текущего филиала
            $hasItems = StoreTransferItem::find()
                ->where([
                    'transfer_id' => $model->id,
                    'source_store_id' => $currentStoreId,
                ])
                ->exists();

            if (!$hasItems) {
                throw new NotFoundHttpException('Нет доступа к этой заявке.');
            }

            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }
}
