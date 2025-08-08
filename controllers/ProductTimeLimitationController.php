<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\Products;
use app\models\ProductTimeLimitation;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * ProductTimeLimitationController implements the CRUD actions for ProductTimeLimitation model.
 */
class ProductTimeLimitationController extends Controller
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
                        'actions' => ['index', 'create', 'update', 'delete', 'view', 'search-products', 'get-product-name'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN
                        ],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all ProductTimeLimitation models.
     * @return mixed
     */
    public function actionIndex()
    {
        // Check if table exists and get its structure
        $tableSchema = Yii::$app->db->schema->getTableSchema('product_time_limitation');
        
        $dataProvider = new ActiveDataProvider([
            'query' => ProductTimeLimitation::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ProductTimeLimitation model.
     * @param string $productId
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($productId)
    {
        return $this->render('view', [
            'model' => $this->findModel($productId),
        ]);
    }

    /**
     * Creates a new ProductTimeLimitation model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ProductTimeLimitation();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'productId' => $model->productId]);
        }

        $products = ArrayHelper::map(
            Products::find()->where(['<>', 'productType', ''])->orderBy('name')->all(),
            'id',
            'name'
        );

        return $this->render('create', [
            'model' => $model,
            'products' => $products,
        ]);
    }

    /**
     * Updates an existing ProductTimeLimitation model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $productId
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($productId)
    {
        $model = $this->findModel($productId);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'productId' => $model->productId]);
        }

        $products = ArrayHelper::map(
            Products::find()->where(['<>', 'productType', ''])->orderBy('name')->all(),
            'id',
            'name'
        );

        return $this->render('update', [
            'model' => $model,
            'products' => $products,
        ]);
    }

    /**
     * Deletes an existing ProductTimeLimitation model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $productId
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($productId)
    {
        $this->findModel($productId)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ProductTimeLimitation model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $productId
     * @return ProductTimeLimitation the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($productId)
    {
        if (($model = ProductTimeLimitation::findOne(['productId' => $productId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Performs search for products
     * @return \yii\web\Response
     */
    public function actionSearchProducts($term)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $products = Products::find()
            ->where(['<>', 'productType', ''])
            ->andWhere(['like', 'name', $term])
            ->orderBy('name')
            ->limit(20)
            ->asArray()
            ->all();
            
        return array_map(function($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
            ];
        }, $products);
    }
    
    /**
     * Get product name by ID
     * @param string $id Product ID
     * @return \yii\web\Response
     */
    public function actionGetProductName($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $product = Products::findOne($id);
        
        if ($product) {
            return [
                'success' => true,
                'name' => $product->name,
            ];
        }
        
        return [
            'success' => false,
        ];
    }
} 