<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\ProductWriteoff;
use app\models\ProductWriteoffItem;
use app\models\ProductWriteoffPhoto;
use app\models\ProductWriteoffSearch;
use app\models\Products;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
                        'actions' => ['index', 'create', 'update', 'view', 'delete-photo', 'export-excel'],
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
                        'actions' => ['admin-index', 'approve-form', 'approve','view',  'delete'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN,
                            User::ROLE_OFFICE,
                            User::ROLE_OFFICE_MANAGER,
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

        // Если не админ/офис/офис менеджер, показываем только списания своего магазина
        if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE, User::ROLE_OFFICE_MANAGER])) {
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
     * Список списаний для администраторов с фильтрацией
     * @return mixed
     */
    public function actionAdminIndex()
    {
        $searchModel = new ProductWriteoffSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->sort = ['defaultOrder' => ['created_at' => SORT_DESC]];

        return $this->render('admin-index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Экспорт списаний в Excel
     * @return void
     */
    public function actionExportExcel()
    {
        $searchModel = new ProductWriteoffSearch();

        // Если не админ/офис/офис менеджер, показываем только списания своего магазина
        if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE, User::ROLE_OFFICE_MANAGER])) {
            $searchModel->store_id = Yii::$app->user->identity->store_id;
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination = false; // Получить все записи

        $writeoffs = $dataProvider->getModels();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Списания');

        // Заголовки
        $headers = ['ID', 'Магазин', 'Продукт', 'Ед. изм.', 'Кол-во запрошено', 'Кол-во утверждено', 'Статус', 'Комментарий', 'Дата создания', 'Создал', 'Дата утверждения', 'Утвердил'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Стиль заголовков
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        $row = 2;
        foreach ($writeoffs as $writeoff) {
            $items = $writeoff->items;

            if (empty($items)) {
                // Запись без позиций
                $sheet->setCellValue('A' . $row, $writeoff->id);
                $sheet->setCellValue('B' . $row, $writeoff->store ? $writeoff->store->name : '-');
                $sheet->setCellValue('C' . $row, '-');
                $sheet->setCellValue('D' . $row, '-');
                $sheet->setCellValue('E' . $row, '-');
                $sheet->setCellValue('F' . $row, '-');
                $sheet->setCellValue('G' . $row, $writeoff->getStatusLabel());
                $sheet->setCellValue('H' . $row, $writeoff->comment ?: '');
                $sheet->setCellValue('I' . $row, date('d.m.Y H:i', strtotime($writeoff->created_at)));
                $sheet->setCellValue('J' . $row, $writeoff->createdBy ? $writeoff->createdBy->fullname : '-');
                $sheet->setCellValue('K' . $row, $writeoff->approved_at ? date('d.m.Y H:i', strtotime($writeoff->approved_at)) : '-');
                $sheet->setCellValue('L' . $row, $writeoff->approvedBy ? $writeoff->approvedBy->fullname : '-');
                $row++;
            } else {
                foreach ($items as $index => $item) {
                    $sheet->setCellValue('A' . $row, $writeoff->id);
                    $sheet->setCellValue('B' . $row, $writeoff->store ? $writeoff->store->name : '-');
                    $sheet->setCellValue('C' . $row, $item->product ? $item->product->name : '-');
                    $sheet->setCellValue('D' . $row, $item->product ? $item->product->mainUnit : '-');
                    $sheet->setCellValue('E' . $row, $item->count);
                    $sheet->setCellValue('F' . $row, $item->approved_count !== null ? $item->approved_count : '-');
                    $sheet->setCellValue('G' . $row, $writeoff->getStatusLabel());
                    $sheet->setCellValue('H' . $row, $index === 0 ? ($writeoff->comment ?: '') : '');
                    $sheet->setCellValue('I' . $row, date('d.m.Y H:i', strtotime($writeoff->created_at)));
                    $sheet->setCellValue('J' . $row, $writeoff->createdBy ? $writeoff->createdBy->fullname : '-');
                    $sheet->setCellValue('K' . $row, $writeoff->approved_at ? date('d.m.Y H:i', strtotime($writeoff->approved_at)) : '-');
                    $sheet->setCellValue('L' . $row, $writeoff->approvedBy ? $writeoff->approvedBy->fullname : '-');
                    $row++;
                }
            }
        }

        // Авто-ширина колонок
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Списания_' . date('Y-m-d_H-i') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
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

        // Логируем КАЖДЫЙ запрос к этому экшену
        Yii::error('actionCreate called. Method: ' . Yii::$app->request->method . ', isPost: ' . (Yii::$app->request->isPost ? 'YES' : 'NO'), 'writeoff');
        Yii::error('PHP upload settings: post_max_size=' . ini_get('post_max_size') . ', upload_max_filesize=' . ini_get('upload_max_filesize'), 'writeoff');

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            // Отладка: посмотрим что приходит
            Yii::error('POST data: ' . print_r($post, true), 'writeoff');
            Yii::error('FILES data: ' . print_r($_FILES, true), 'writeoff');
            Yii::error('Content-Type: ' . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'NOT SET'), 'writeoff');
            Yii::error('Content-Length: ' . (isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 'NOT SET'), 'writeoff');

            if (isset($post['items']) && is_array($post['items'])) {
                $transaction = Yii::$app->db->beginTransaction();

                try {
                    $model->comment = $post['comment'] ?? null;
                    $model->status = ProductWriteoff::STATUS_NEW;

                    Yii::error('Trying to save model: ' . json_encode($model->attributes), 'writeoff');

                    if (!$model->save()) {
                        $errors = json_encode($model->errors);
                        Yii::error('Model validation errors: ' . $errors, 'writeoff');
                        throw new \Exception('Ошибка сохранения списания: ' . $errors);
                    }

                    Yii::error('Model saved successfully, ID: ' . $model->id, 'writeoff');

                    $hasItems = false;
                    foreach ($post['items'] as $productId => $count) {
                        if (empty($count) || $count <= 0) {
                            continue;
                        }

                        $item = new ProductWriteoffItem();
                        $item->writeoff_id = $model->id;
                        $item->product_id = $productId;
                        $item->count = $count;

                        Yii::error('Trying to save item: ' . json_encode($item->attributes), 'writeoff');

                        if (!$item->save()) {
                            $itemErrors = json_encode($item->errors);
                            Yii::error('Item validation errors: ' . $itemErrors, 'writeoff');
                            throw new \Exception('Ошибка сохранения позиции списания: ' . $itemErrors);
                        }

                        Yii::error('Item saved successfully', 'writeoff');
                        $hasItems = true;
                    }

                    if ($hasItems) {
                        // Сохраняем фотографии
                        $this->savePhotos($model);

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
                        // Сохраняем фотографии
                        $this->savePhotos($model);

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
     * Форма подтверждения списания
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionApproveForm($id)
    {
        $model = $this->findModel($id);

        if ($model->status === ProductWriteoff::STATUS_APPROVED) {
            Yii::$app->session->setFlash('error', 'Списание уже утверждено');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('approve-form', [
            'model' => $model,
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

            // Отправляем уведомление в Telegram
            $model->sendApprovalNotification();

            // Создаем акт списания в iiko
            $iiko = new \app\models\Iiko();
            $result = $iiko->createWriteoffDoc($model);

            if ($result === true) {
                Yii::$app->session->setFlash('success', 'Списание утверждено и акт списания создан в iiko');
            } else {
                // Если произошла ошибка при создании акта в iiko, выводим предупреждение
                Yii::$app->session->setFlash('warning', 'Списание утверждено, но возникла ошибка при создании акта в iiko: ' . $result);
            }
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при утверждении списания');
        }

        return $this->redirect(['admin-index']);
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

        return $this->redirect(['admin-index']);
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
            if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE, User::ROLE_OFFICE_MANAGER])) {
                if ($model->store_id != Yii::$app->user->identity->store_id) {
                    throw new NotFoundHttpException('Нет доступа к этому списанию.');
                }
            }

            return $model;
        }

        throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }

    /**
     * Удаление фото
     * @param integer $id
     * @return mixed
     */
    public function actionDeletePhoto($id)
    {
        $photo = ProductWriteoffPhoto::findOne($id);

        if ($photo === null) {
            throw new NotFoundHttpException('Фото не найдено.');
        }

        $writeoff = $photo->writeoff;

        // Проверяем права доступа
        if (!in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE, User::ROLE_OFFICE_MANAGER])) {
            if ($writeoff->store_id != Yii::$app->user->identity->store_id) {
                throw new NotFoundHttpException('Нет доступа к этому фото.');
            }
        }

        // Проверяем, можно ли редактировать списание
        if (!$writeoff->canEdit()) {
            Yii::$app->session->setFlash('error', 'Нельзя удалять фото у утвержденного списания');
            return $this->redirect(['view', 'id' => $writeoff->id]);
        }

        $photo->delete();
        Yii::$app->session->setFlash('success', 'Фото удалено');

        return $this->redirect(['view', 'id' => $writeoff->id]);
    }

    /**
     * Сохранение фотографий
     * @param ProductWriteoff $model
     */
    protected function savePhotos($model)
    {
        Yii::error('=== START savePhotos ===', 'writeoff-photos');

        $photos = UploadedFile::getInstancesByName('photos');

        Yii::error('Photos count: ' . (is_array($photos) ? count($photos) : 0), 'writeoff-photos');
        Yii::error('Photos data: ' . print_r($photos, true), 'writeoff-photos');

        if (empty($photos)) {
            Yii::error('No photos uploaded', 'writeoff-photos');
            return;
        }

        // Создаем директорию если её нет
        $uploadPath = Yii::getAlias('@webroot/uploads/writeoff-photos');
        Yii::error('Upload path: ' . $uploadPath, 'writeoff-photos');

        if (!is_dir($uploadPath)) {
            Yii::error('Directory does not exist, creating...', 'writeoff-photos');
            $created = mkdir($uploadPath, 0755, true);
            Yii::error('Directory created: ' . ($created ? 'YES' : 'NO'), 'writeoff-photos');
        } else {
            Yii::error('Directory already exists', 'writeoff-photos');
        }

        foreach ($photos as $index => $photo) {
            Yii::error("Processing photo #$index", 'writeoff-photos');
            Yii::error("Photo details: " . print_r([
                'name' => $photo->name,
                'type' => $photo->type,
                'size' => $photo->size,
                'extension' => $photo->extension,
                'hasError' => $photo->hasError,
                'error' => $photo->error,
            ], true), 'writeoff-photos');

            // Генерируем уникальное имя файла
            $filename = uniqid() . '_' . time() . '.' . $photo->extension;
            $filePath = $uploadPath . '/' . $filename;

            Yii::error("Saving to: $filePath", 'writeoff-photos');

            // Сохраняем файл
            if ($photo->saveAs($filePath)) {
                Yii::error("File saved successfully", 'writeoff-photos');

                // Создаем запись в БД
                $photoModel = new ProductWriteoffPhoto();
                $photoModel->writeoff_id = $model->id;
                $photoModel->filename = $filename;

                if ($photoModel->save()) {
                    Yii::error("DB record saved successfully, ID: " . $photoModel->id, 'writeoff-photos');
                } else {
                    Yii::error("Failed to save DB record: " . json_encode($photoModel->errors), 'writeoff-photos');
                }
            } else {
                Yii::error("Failed to save file", 'writeoff-photos');
            }
        }

        Yii::error('=== END savePhotos ===', 'writeoff-photos');
    }
}
