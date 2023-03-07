<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $data app\models\Orders */

$i = 0;
$date = date("d.m.Y");
$this->title = "Заказы по продуктам на " . $date;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('<i class="glyphicon glyphicon-download-alt"></i> Excel', ['stock-by-product-excel'], ['class' => 'btn btn-success btn-fill']) ?>
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <table class="table table-hover table-striped">
            <thead>
            <tr>
                <th width="40">#</th>
                <th>Продукт</th>
                <th class="text-center">Ед. Изм.</th>
                <th class="text-center">Остаток</th>
                <th class="text-center">Заказан</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $row): ?>
                <?php $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= $row['name'] ?></td>
                    <td class="text-center"><?= $row['mainUnit'] ?></td>
                    <td class="text-center"><?= $row['inStock'] ?></td>
                    <td class="text-center"><?= $row['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
