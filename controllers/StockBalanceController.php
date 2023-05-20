<?php

namespace app\controllers;

use Yii;
use app\models\StockBalance;
use app\models\StockBalanceSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * StockBalanceController implements the CRUD actions for StockBalance model.
 */
class StockBalanceController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all StockBalance models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new StockBalanceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single StockBalance model.
     * @param string $store
     * @param string $product
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($store, $product)
    {
        return $this->render('view', [
            'model' => $this->findModel($store, $product),
        ]);
    }

    /**
     * Creates a new StockBalance model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new StockBalance();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'store' => $model->store, 'product' => $model->product]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing StockBalance model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $store
     * @param string $product
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($store, $product)
    {
        $model = $this->findModel($store, $product);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'store' => $model->store, 'product' => $model->product]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing StockBalance model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $store
     * @param string $product
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($store, $product)
    {
        $this->findModel($store, $product)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the StockBalance model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $store
     * @param string $product
     * @return StockBalance the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($store, $product)
    {
        if (($model = StockBalance::findOne(['store' => $store, 'product' => $product])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
