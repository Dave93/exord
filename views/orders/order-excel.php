<?php

use app\models\Orders;
use app\models\Products;
use app\models\UserCategories;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Orders */
/* @var $products array */

$groupId = null;
?>
<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
<table border="1">
    <thead>
    <tr>
        <th colspan="4"><?= $model->store->name ?> - #<?= $model->id ?>
            от <?= date("d.m.Y", strtotime($model->date)) ?></th>
    </tr>
    <tr>
        <th>Наименование</th>
        <th>Ед. Изм.</th>
        <th>Коиличество</th>
        <th>Факт</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $key => $product): ?>
        <tr>
            <td><?= $product['name'] ?></td>
            <td><?= $product['mainUnit'] ?></td>
            <td><?= $product['quantity'] ?></td>
            <td></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>