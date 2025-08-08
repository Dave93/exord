<?php

use app\models\Orders;
use app\models\Products;
use app\models\UserCategories;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
use app\models\Dashboard;
use app\models\OrderItems;

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
        <th>Готовность</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($groupedProducts as $group) {?>
        <tr><th colspan="4" class="text-center"><?=($group['name'] ? $group['name'] : 'Остальные продукты')?></th></tr>
        <?php foreach ($group['products'] as $item): ?>
            <?php
            $i++;
            $price = $item['price'] * (100 + $model->user->percentage) / 100;
            $priceString = Dashboard::price($price);
            $sum = $item['storeQuantity'] * $price;
            $total += $sum;
            ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $item['name'] ?></td>
                <td width="30" align="center"><?= $item['mainUnit'] ?></td>
                <td width="100" align="center"><?= $item['storeQuantity'] ?></td>
                <td align="center"></td>
            </tr>
        <?php endforeach; ?>
    <?php }?>
    </tbody>
</table>