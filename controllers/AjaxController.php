<?php

namespace app\controllers;

use app\models\Event;
use app\models\Iiko;
use app\models\Products;
use Yii;
use yii\db\mssql\PDO;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;

class AjaxController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['*'],
                'rules' => [
                    [
                        'actions' => ['get-product', 'update-product', 'sync-iiko', 'events', 'search-product'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionGetProduct()
    {
        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');

        $model = Products::findOne($id);

        $data['id'] = $model->id;
        $data['name'] = $model->name;
        $data['price_start'] = $model->price_start;
        $data['price_end'] = $model->price_end;
        $data['delta'] = $model->delta;
        $data['zone'] = $model->zone;
        $data['description'] = $model->description;
        $data['min_balance'] = $model->minBalance;
        $data['show_on_report'] = $model->showOnReport;

        $response['state'] = "OK";
        $response['data'] = $data;
        return $response;
    }

    public function actionUpdateProduct()
    {
        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $product = Yii::$app->request->post('Products');

        if (empty($product) || empty($product['id'])) {
            $response['state'] = "Error";
            $response['message'] = "Не правильные данные";
            $response['post'] = $product;
            return $response;
        }
        $model = Products::findOne($product['id']);

        if ($model == null) {
            $response['state'] = "Error";
            $response['message'] = "Продукт не найден";
            return $response;
        }

        $model->price_start = $product['price_start'];
        $model->price_end = $product['price_end'];
        $model->alternative_price = $product['alternative_price'];
        $model->alternative_date = $product['alternative_date'];
        $model->delta = $product['delta'];
        $model->zone = $product['zone'];
        $model->description = $product['description'];
        $model->minBalance = $product['minBalance'];
        $model->showOnReport = $product['showOnReport'];
        if (!$model->save()) {
            $response['state'] = "Error";
            $response['message'] = reset($model->firstErrors);
            return $response;
        }

        $response['state'] = "OK";
        $response['post'] = $product;
        return $response;
    }

    public function actionSyncIiko()
    {
        set_time_limit(0);
        error_reporting(E_ERROR);
        ini_set('memory_limit', -1);
        ini_set('display_errors', 1);


        $response = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $iiko = new Iiko();
        if (!$iiko->auth()) {
            $response['state'] = "Error";
            $response['message'] = "Ошибка авторизации в iiko";
            return $response;
        }
        $iiko->departments();
        $iiko->suppliers();
        $iiko->stores();
        $iiko->products();
        $iiko->groups();

        $now = date('Y-m-d');
        $start = date('Y-m-d', strtotime($now . ' -1 days'));
        $iiko->incomingPrices($start, $now);

        $response['state'] = "OK";
        return $response;
    }

    public function actionEvents($start = null, $end = null, $_ = null)
    {
        $events = [];
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->db->createCommand("select id,date,count(*) as total from orders where date between :s and :e group by date")
            ->bindParam(":s", date("Y-m-d", strtotime($start)), PDO::PARAM_STR)
            ->bindParam(":e", date("Y-m-d", strtotime($end)), PDO::PARAM_STR)
            ->queryAll();
        foreach ($data AS $row) {
            //Testing
            $Event = new Event();
            $Event->id = $row['id'];
            $Event->title = 'Заказы: ' . $row['total'];
            $Event->start = date('Y-m-d\T00:00P', strtotime($row['date']));
//            $Event->end = date('Y-m-d\T23:59P', strtotime($row['date']));
            $Event->allDay = true;
            $events[] = $Event;
        }

        return $events;
    }

    public function actionSearchProduct($q = null)
    {
        $p = '';
        $query = new Query();
        $ps = $query->select('category_id')->from('user_categories')->where(['user_id' => Yii::$app->user->id])->column();
        foreach ($ps as $row) {
            $p .= "'{$row}',";
        }
        $p = substr($p, 0, -1);
        $query->select('id,name')->from('products')
            ->where('name LIKE "' . $q . '%" and parentId="933aedd9-6cd9-44bb-9d72-00008e9b3cb5"');
        $data = $query->all();
        $out = [];
        foreach ($data as $s) {
            $out[] = [
                'id' => $s['id'],
                'value' => $s['name']
            ];
        }
        echo Json::encode($out);
    }

}
