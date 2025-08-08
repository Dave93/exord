<?php

namespace app\controllers;


use app\components\AccessRule;
use app\models\OrderSearch;
use app\models\ProductGroups;
use app\models\ProductGroupsLink;
use app\models\ProductGroupsSearch;
use app\models\Products;
use app\models\User;
use yii\db\mssql\PDO;
use yii\filters\AccessControl;
use yii\web\Controller;
use Yii;
use yii\web\NotFoundHttpException;

class ProductGroupsController extends Controller
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
                        'actions' => ['index', 'update', 'add'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_STOCK,
                            User::ROLE_ADMIN,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new ProductGroupsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                $data = Yii::$app->request->post();
                ProductGroupsLink::deleteAll(['productGroupId' => $model->id]);
                foreach ($data['ProductGroups']['productIds'] as $product) {
                    $productGroupLink = new ProductGroupsLink();
                    $productGroupLink->productGroupId = $model->id;
                    $productGroupLink->productId = $product;
                    $productGroupLink->save();
                }
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    public function actionAdd()
    {
        $model = new ProductGroups();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('add', [
            'model' => $model
        ]);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ProductGroups::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}