<?php

namespace app\controllers;


use app\components\AccessRule;
use app\models\TgusersSearch;
use app\models\User;
use app\models\UserSearch;
use app\models\WriteOff;
use app\models\WriteOffsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use Yii;

class WriteOffsController extends Controller
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
                        'actions' => ['index', 'create', 'delete', 'update', 'view'],
                        'allow' => true,
                        'roles' => [
                            User::ROLE_ADMIN
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new WriteOffsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = WriteOff::findOne($id);

        if (Yii::$app->request->post()) {

            $data = Yii::$app->request->post();

            $model->load($data);
            $model->save();

        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
}