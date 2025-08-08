<?php

namespace app\controllers;


use app\components\AccessRule;
use app\models\TgusersSearch;
use app\models\User;
use app\models\UserSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use Yii;

class TgusersController extends Controller
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
        $searchModel = new TgusersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = TgusersSearch::findOne($id);

        if (Yii::$app->request->post()) {

            $data = Yii::$app->request->post();
            if ($data['TgusersSearch']['active'] == 1 && empty($data['TgusersSearch']['user_id'])) {
                Yii::$app->session->setFlash('error', 'Пользователь не выбран');
            }

            $model->load($data);
            $model->save();


            $message = "";

            if ($data['TgusersSearch']['active'] == 1) {
                $message = "Ваш аккаунт активирован";
            } else {
                $message = "Ваш аккаунт деактивирован";
            }

            /**
             * create http post request to https://writeoffbot.lesailes.uz/send_message with bearer token and chat_id and text
             */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://writeoffbot.lesailes.uz/send_message");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'tg_id' => $model->tg_id,
                'message' => $message
            ]));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

            // set json response
//            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//
//            ));

            $apiToken = "WD6cassbK975";
            $buff = base64_encode($apiToken);

// random string with 6 characters
            $randomString = substr(md5(mt_rand()), 0, 6);
            $hexBuffer = $randomString.$buff;
            $hex = bin2hex($hexBuffer);
            $headers = array(
                "authorization: Bearer $hex",
                'Content-Type: application/json',
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            return $this->redirect(['/tgusers/index']);

        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
}