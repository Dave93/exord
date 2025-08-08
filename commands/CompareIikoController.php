<?php


namespace app\commands;
use app\models\OrderItems;
use app\models\Orders;
use Yii;
use app\models\Iiko;
use app\models\Stores;
use yii\console\Controller;

class CompareIikoController extends Controller
{

    public function actionStart() {


        $iiko = new Iiko();
        $iiko->auth();

        $outgoingDocs = $iiko->getOutgoingDocs('2023-06-01', '2023-06-30');

        $outgoingDocs = $outgoingDocs['document'];

        $arOrders = Orders::find()->where('addDate>=:e', [':e'=>'2023-06-01 00:00:00'])
            ->andWhere('addDate<=:s', [':s' => '2023-06-30 23:59:59'])
            ->all();

        $orderIds = [];

        $arOrderProducts = [];

        foreach ($arOrders as $order) {
            $orderIds[] = $order->id;
            $arOrderProducts[$order->id] = [];
        }

        $orderProducts = OrderItems::find()->where(['IN','orderId', $orderIds])->all();

        foreach ($orderProducts as $orderProduct) {
            $arOrderProducts[$orderProduct->orderId][$orderProduct->productId] = $orderProduct->productId;
        }
//echo '<pre>'; print_r($outgoingDocs); echo '</pre>';
        $outgoingDocs = array_filter($outgoingDocs, function ($doc) use ($orderIds) {
            $docNumber = $doc['documentNumber'];
            $arNumber = explode('-', $docNumber);
            $orderId = end($arNumber);
            return in_array($orderId, $orderIds);
        });

        $outgoingDocs = array_filter($outgoingDocs, function ($doc) use ($arOrderProducts) {
            $docProducts = $doc['items']['item'];
            $docNumber = $doc['documentNumber'];
            $arNumber = explode('-', $docNumber);
            $orderId = end($arNumber);
            $orderProducts = $arOrderProducts[$orderId];
            if (isset($docProducts['productId'])) {
                $docProducts = [$docProducts];
            }
            $docProducts = array_filter($docProducts, function ($docProduct) use ($orderProducts) {
                $docProductId = $docProduct['productId'];
                return !in_array($docProductId, $orderProducts);
            });
            return count($docProducts) > 0;
        });


        foreach ($outgoingDocs as $outgoingDoc) {
            $docProducts = $outgoingDoc['items']['item'];
            $docNumber = $outgoingDoc['documentNumber'];
            $arNumber = explode('-', $docNumber);
            $orderId = end($arNumber);
            $orderProducts = $arOrderProducts[$orderId];
            if (isset($docProducts['productId'])) {
                $docProducts = [$docProducts];
            }
            $docProducts = array_filter($docProducts, function ($docProduct) use ($orderProducts) {
                $docProductId = $docProduct['productId'];
                return in_array($docProductId, $orderProducts);
            });
            echo '<pre>'; print_r($orderId); echo '</pre>';
            echo '<pre>'; print_r($docProducts); echo '</pre>';
        }

//        echo '<pre>'; print_r($outgoingDocs); echo '</pre>';
    }
}