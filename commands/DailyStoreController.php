<?php

namespace app\commands;

use app\models\DailyStoreBalance;
use app\models\DailyStoreProduct;
use Yii;
use app\models\Iiko;
use app\models\Stores;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class DailyStoreController extends Controller
{
    public function actionCreateStoreBalance()
    {
        $query = Stores::find();
        $stores = $query->all();
        foreach ($stores as $store) {
            $dailyStoreBalance = new DailyStoreBalance();
            $storeArr = $store->toArray();
            $dailyStoreBalance->store_id = $storeArr['id'];
            $dailyStoreBalance->store_name = $storeArr['name'];
            $dailyStoreBalance->save();

            $iiko = new Iiko();
            $data = $iiko->getReport($storeArr['id']);

            foreach ($data as $d) {
                $dailyStoreProduct = new DailyStoreProduct();
                $dailyStoreProduct->store_id = $storeArr['id'];
                $dailyStoreProduct->daily_store_balance_id = $dailyStoreBalance->id;
                $dailyStoreProduct->product_id = $d['product'];
                $dailyStoreProduct->quantity = $d['amount'];
                $dailyStoreProduct->save();
            }
        }
    }
}