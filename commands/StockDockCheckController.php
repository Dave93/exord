<?php

namespace app\commands;

use app\models\DailyStoreBalance;
use app\models\DailyStoreProduct;
use app\models\Orders;
use Yii;
use app\models\Iiko;
use app\models\Stores;
use yii\console\Controller;
use yii\helpers\ArrayHelper;


class StockDockCheckController extends Controller
{
    public $orderId;
    public $useOrderDate = false;

    public function options($actionID)
    {
        return ['orderId', 'useOrderDate'];
    }

    public function actionCheck() {
        echo '<pre>'; print_r($this->orderId); echo '</pre>';
        if ($this->orderId) {
            $model = Orders::findOne(['id' => $this->orderId]);
            echo '<pre>'; print_r($model); echo '</pre>';
            if ($model) {
//                echo '<pre>'; print_r($model); echo '</pre>';
                $iiko = new Iiko();
                $iiko->auth();
//echo '<pre>'; print_r($model); echo '</pre>';
                $customDate = $this->useOrderDate ? $model->date : null;
                $outDoc = $iiko->supplierOutStockDoc($model, true, $customDate);
                if (!$outDoc) {
                    echo '<pre>'; print_r($outDoc); echo '</pre>';
                }
            }
        }
    }
}