<?php

namespace app\controllers;

use app\components\AccessRule;
use app\models\OrderSearch;
use app\models\User;
use yii\filters\AccessControl;
use Yii;
use yii\web\Controller;

class ProductsUsageController extends Controller {

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
                        'actions' => ['create-usage'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_COOK
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionCreateUsage()
    {
        $searchModel = new OrderSearch();
        $searchModel->userId = Yii::$app->user->id;
//        $searchModel->state = 0;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);
        $dataProvider->sort = ['defaultOrder' => ['id' => SORT_ASC]];

        return $this->render('usage', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}