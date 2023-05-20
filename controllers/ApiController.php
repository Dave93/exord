<?php

namespace app\controllers;

use app\models\OrderItems;
use app\models\Orders;
use app\models\User;
use Yii;
use yii\db\mssql\PDO;
use yii\web\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    const ANDROID_VERSION = '1.0';
    const KEY = 'RN9p@?2VZH&LnhfCM6dBY3M+5u6tCvfkRXz9krHT$^4RyHHnLhRJ&@YfamK_f#3KKNwn_eCv--w@4dXSU25Ypz6akx5kWtCQdbqRqV@SwExAMu9^qQM+^Ngrpxp@YKbC_RXKZay^cm_gavkzv_wkFzVPDvE4RNRGpxEXQD*yEDkagkK3D#u*LsJD6jJ3PU!u_QS@Az7F?&!$Z58XXE=Qy=V9N4&K7^m=&HnuVxrRspc#ZBEeZYLjg!CXc*&8SY*Y';

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    private function checkRequest($key)
    {
        $r = [];
        $r['access'] = true;
        if ($key !== self::KEY) {
            $response['state'] = "ERROR";
            $response['stateCode'] = 403;
            $response['message'] = "Недопустимые параметры авторизации!";
            $r['access'] = false;
            $r['response'] = $response;
        }
        return $r;
    }

    public function actionAuth()
    {
        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $key = Yii::$app->request->post('key');
        $system = Yii::$app->request->post('system');
        $username = Yii::$app->request->post('username');
        $password = Yii::$app->request->post('password');

        $check = $this->checkRequest($key);
        if (!$check['access'])
            return $check['response'];

        if (empty($username) || empty($password)) {
            $response['state'] = "Error";
            $response['stateCode'] = 403;
            $response['message'] = "Заполните все поля";
            return $response;
        }

        $model = User::findOne(['username' => $username, 'role' => 4, 'state' => 1]);
        if ($model != null && $model->validatePassword($password)) {
            $data = [];
            $data['id'] = $model->id;
            $data['role'] = $model->role;
            $data['name'] = $model->fullname;
            $data['username'] = $model->username;
            $data['phone'] = $model->phone;
            $data['email'] = $model->email;
            $data['regDate'] = $model->regDate;

            $model->lastVisit = date("Y-m-d H:i:s");
            $model->save();

            $response['state'] = "OK";
            $response['stateCode'] = 200;
            $response['data'] = $data;
        } else {
            $response['state'] = "Error";
            $response['stateCode'] = 403;
            $response['message'] = "Логин или пароль введен не правильно";
        }
        return $response;
    }

    public function actionGetProducts()
    {
        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $key = Yii::$app->request->post('key');

        $check = $this->checkRequest($key);
        if (!$check['access'])
            return $check['response'];

        $data = Yii::$app->db->createCommand("select id,name,mainUnit as unit from products")->queryAll();
        $response['state'] = "OK";
        $response['stateCode'] = 200;
        $response['data'] = $data;
        return $response;
    }

    public function actionGetTodayOrder()
    {
        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $key = Yii::$app->request->post('key');
        $guid = Yii::$app->request->post('guid');

        $check = $this->checkRequest($key);
        if (!$check['access'])
            return $check['response'];

        $sql = "select o.date,oi.productId as product,oi.supplierQuantity as quantity,oi.purchaseQuantity,oi.price,oi.supplierDescription as description from orders o
                left join order_items oi on oi.orderId=o.id
                where o.date=:d and oi.supplierQuantity>0";
        $data = Yii::$app->db->createCommand($sql)->bindValue(":d", date("Y-m-d"), PDO::PARAM_STR)->queryAll();
        $response['state'] = "OK";
        $response['stateCode'] = 200;
        $response['data'] = $data;
        return $response;
    }

    public function actionUpdateOrder()
    {
        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $key = Yii::$app->request->post('key');
        $userId = Yii::$app->request->post('userId');
        $date = Yii::$app->request->post('date');
        $productId = Yii::$app->request->post('productId');
        $quantity = Yii::$app->request->post('quantity');
        $price = Yii::$app->request->post('price');
        $description = Yii::$app->request->post('description');

        $check = $this->checkRequest($key);
        if (!$check['access'])
            return $check['response'];

        $user = User::findOne(['id' => $userId]);
        $order = Orders::findOne(['date' => $date]);

        $model = OrderItems::findOne(['orderId' => $order->id, 'productId' => $productId]);
        $model->supplierId = $user->supplier_id;
        $model->purchaseQuantity = $quantity;
        $model->price = $price;
        $model->supplierDescription = $description;
        $model->save();

        $response['state'] = "OK";
        $response['stateCode'] = 200;
        return $response;
    }
}
