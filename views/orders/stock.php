<?php

use app\models\Orders;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $orders array */
/* @var $date string */
/* @var $orderId integer */

$this->title = "Заказы на: " . date("d.m.Y", strtotime($date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";
foreach ($orders as $order) {
    $active = "";
    $products = Orders::getOrderProducts($order['id']);
    if (empty($products))
        continue;

    if (($i == 0 && empty($orderId)) || $orderId == $order['id'])
        $active = "active";
    $color = '';
    if ($order['state'] == 1) {
        $color = 'bg-orange';
    }
    $d = date('d.m H:i', strtotime($order['addDate']));
    $list .= <<<HTML
        <li class="{$active} {$color}"><a href="#st-{$order['id']}" data-toggle="tab">{$order['name']} (#{$order['id']} - {$d})</a></li>
HTML;
    $content .= $this->render('_stock-tab', [
        'order' => $order,
        'products' => $products,
        'active' => $active
    ]);
    $i++;
}
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right"></p>
    </div>
    <hr>
    <div class="content table-responsive">
        <?php if (!empty($orders)): ?>
            <div class="orders-list">
                <div class="row">
                    <div class="col-md-4 col-lg-3">
                        <h4 class="title" style="padding-left: 20px; padding-bottom: 20px;">Филиалы</h4>
                        <ul class="nav nav-tabs tabs-left">
                            <?= $list ?>
                        </ul>
                    </div>

                    <div class="col-md-8 col-lg-9">
                        <h4 class="title" style="padding-bottom: 20px">Продукты</h4>
                        <div class="tab-content">
                            <?= $content ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">На сегодня не найден заказы</div>
        <?php endif; ?>
    </div>
</div>
