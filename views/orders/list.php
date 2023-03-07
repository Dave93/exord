<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $orders array */
/* @var $date string */

$this->title = "Заказы на " . date("d.m.Y", strtotime($date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";
foreach ($orders as $order) {
    $active = "";
    if ($i == 0)
        $active = "active";
    $list .= <<<HTML
        <li class="{$active}"><a href="#st-{$order['id']}" data-toggle="tab">{$order['name']} (#{$order['id']})</a></li>
HTML;

    $content .= $this->render('_items-tab', [
        'order' => $order,
        'active' => $active
    ]);
    $i++;
}
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
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
            <div class="alert alert-danger">На эту дату не найден заказы</div>
        <?php endif; ?>
    </div>
</div>
