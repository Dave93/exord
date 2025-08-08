<?php

namespace app\controllers\api;

use app\models\WriteOff;
use yii\rest\ActiveController;
use Yii;

class TguserController extends ActiveController
{
    public $modelClass = 'app\models\TgUsers';

    public function actions()
    {
        $actions = parent::actions();

        // disable the "delete" and "create" actions
        unset($actions['view'], $actions['delete'], $actions['update'], $actions['create']);

        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }


    public function checkAccess($action, $model = null, $params = [])
    {

        if ($action == 'index') {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }
        // check if the user can access $action and $model
        // throw ForbiddenHttpException if access should be denied
        if ($action === 'update' || $action === 'delete') {
            if ($model->author_id !== \Yii::$app->user->id)
                throw new \yii\web\ForbiddenHttpException(sprintf('You can only %s articles that you\'ve created.', $action));
        }
    }

    public function actionCheckUser() {
        $request = Yii::$app->request;

        $bearerToken = $request->getHeaders()->get('Authorization');
        $bearerToken = str_replace('Bearer ', '', $bearerToken);

        if (empty($bearerToken)) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $hex = hex2bin($bearerToken);
        $base64 = base64_decode(substr($hex, 6));
//echo '<pre>'; print_r($base64); echo '</pre>';return;
        if ($base64 != "WD6cassbK975") {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }
        if ($request->get('tg_id') == null) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $tgUser = \app\models\TgUsers::findOne(['tg_id' => $request->get('tg_id')]);
        if (!$tgUser) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        if ($tgUser['active'] == 0) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        return [
            'success' => true,
           'data' => $tgUser
        ];
    }

    public function actionCreate() {
        $request = Yii::$app->request;
        $rawBody = $request->getRawBody(); // returns the raw HTTP request body.
        $postData = json_decode($rawBody, true); // decode the JSON data to associative array.

        $bearerToken = $request->getHeaders()->get('Authorization');
        $bearerToken = str_replace('Bearer ', '', $bearerToken);

        if (empty($bearerToken)) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $hex = hex2bin($bearerToken);
        $base64 = base64_decode(substr($hex, 6));

        if ($base64 != "WD6cassbK975") {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        /*
         * validate data from $postData and throw exception if invalid data provided. tg_id and name are required.
         *
         */

        if (empty($postData['tg_id']) || empty($postData['name']) || empty($postData['phone'])) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $tgUser = \app\models\TgUsers::findOne(['tg_id' => $postData['tg_id']]);
        if ($tgUser) {
            if ($tgUser['active'] == 1) {
                return [
                    'success' => true,
                    'data' => $postData,
                ];
            }
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $tgUser = new \app\models\TgUsers();
        $tgUser->tg_id = (string)$postData['tg_id'];
        $tgUser->name = $postData['name'];
        $tgUser->phone = $postData['phone'];
        $tgUser->active = 0;
        $tgUser->save();

        return [
            'success' => true,
            'data' => $postData,
        ];
    }

    public function actionPostVideo() {
        $request = Yii::$app->request;
        $rawBody = $request->getRawBody(); // returns the raw HTTP request body.
        $postData = json_decode($rawBody, true); // decode the JSON data to associative array.

        $bearerToken = $request->getHeaders()->get('Authorization');
        $bearerToken = str_replace('Bearer ', '', $bearerToken);

        if (empty($bearerToken)) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $hex = hex2bin($bearerToken);
        $base64 = base64_decode(substr($hex, 6));

        if ($base64 != "WD6cassbK975") {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        if (empty($postData['tg_id']) || empty($postData['video_id'])) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $botToken = "6585275308:AAFlMtHMFXXH-_b1jAuBO4xqA5bW5Vi-b_w";
        $fileId = $postData['video_id'];

        $url = "https://api.telegram.org/bot" . $botToken . "/getFile?file_id=" . $fileId;

// Get the file_path
        $response = file_get_contents($url);
        $response = json_decode($response);
        $webroot = Yii::getAlias('@webroot');

        if ($response->ok) {
            $filePath = $response->result->file_path;

            // Download the file
            $fileUrl = "https://api.telegram.org/file/bot" . $botToken . "/" . $filePath;
            $fileData = file_get_contents($fileUrl);
//            echo '<pre>'; print_r($fileData); echo '</pre>';return;
            $filePath = '/uploads/videos/'.$fileId.'.mp4';

            // Save the file
            file_put_contents($webroot.$filePath, $fileData);

            $tgUser = \app\models\TgUsers::findOne(['tg_id' => $postData['tg_id']]);

            $writeOff = new WriteOff();
            $writeOff->tg_user_id = $tgUser->id;
            $writeOff->user_id = $tgUser->user_id;
            $writeOff->video = $filePath;
            $writeOff->save();

            return [
                'success' => true,
                'path' =>$webroot.$filePath,
                'data' => $postData,
            ];
//            $lastVideoPost = WriteOff::find()->orderBy(['id' => SORT_DESC])->one();
        } else {
            throw new \yii\web\ForbiddenHttpException($response->description);
        }

    }

    public function actionPostCalendarMessage() {
        $request = Yii::$app->request;
        $rawBody = $request->getRawBody(); // returns the raw HTTP request body.
        $postData = json_decode($rawBody, true); // decode the JSON data to associative array.

        $bearerToken = $request->getHeaders()->get('Authorization');
        $bearerToken = str_replace('Bearer ', '', $bearerToken);

        if (empty($bearerToken)) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $hex = hex2bin($bearerToken);
        $base64 = base64_decode(substr($hex, 6));

        if ($base64 != "WD6cassbK975") {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        if (empty($postData['tg_id']) || empty($postData['message_id'])) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $tgUser = \app\models\TgUsers::findOne(['tg_id' => $postData['tg_id']]);

        // get last write off by tg_id
        $writeOff = \app\models\WriteOff::find()->where(['tg_user_id' => $tgUser->id])->orderBy(['id' => SORT_DESC])->one();
        if (!$writeOff) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $writeOff->delete_message_id = $postData['message_id'];
        $writeOff->save();

        return [
            'success' => true,
            'data' => $postData,
        ];

    }

    public function actionPostCalendarDate() {
        $request = Yii::$app->request;
        $rawBody = $request->getRawBody(); // returns the raw HTTP request body.
        $postData = json_decode($rawBody, true); // decode the JSON data to associative array.

        $bearerToken = $request->getHeaders()->get('Authorization');
        $bearerToken = str_replace('Bearer ', '', $bearerToken);

        if (empty($bearerToken)) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $hex = hex2bin($bearerToken);
        $base64 = base64_decode(substr($hex, 6));

        if ($base64 != "WD6cassbK975") {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        if (empty($postData['tg_id']) || empty($postData['date'])) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $tgUser = \app\models\TgUsers::findOne(['tg_id' => $postData['tg_id']]);

        // get last write off by tg_id
        $writeOff = \app\models\WriteOff::find()->where(['tg_user_id' => $tgUser->id])->orderBy(['id' => SORT_DESC])->one();
        if (!$writeOff) {
            throw new \yii\web\ForbiddenHttpException('Access is forbidden');
        }

        $writeOff->date = strtotime($postData['date']);
        $writeOff->save();

        if ($writeOff->delete_message_id) {
            /**
             * create http post request to https://writeoffbot.lesailes.uz/send_message with bearer token and chat_id and text
             */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://writeoffbot.lesailes.uz/delete_message");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'tg_id' => $postData['tg_id'],
                'message_id' => $writeOff->delete_message_id
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

            $writeOff->delete_message_id = null;
            $writeOff->save();
        }

        return [
            'success' => true,
            'data' => $postData,
        ];

    }

}