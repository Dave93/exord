<?php

use app\models\Dashboard;
use app\models\OrderItems;
use app\models\Orders;

/* @var $this yii\web\View */
/* @var $model Orders */

$i = 0;
$total = 0;
$items = OrderItems::find()->where('orderId=:id and storeQuantity>0', [':id' => $model->id])->all();
?>
<h2>РАСХОДНАЯ НАКЛАДНАЯ</h2>
<p>Номер документа: <?= $model->id ?></p>
<p>Дата документа: <?= date('d.m.Y H:i', strtotime($model->date)) ?></p>
<p>Поставщик: Центральный склад Chain</p>
<p>Получатель: <?= $model->store->name ?></p>
<p>Примечание: <?= $model->comment ?></p>

<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
<table border="1" width="100%" cellpadding="0" cellspacing="0">
    <thead>
    <tr>
        <th>№</th>
        <th>Продукт</th>
        <th>Ед. изм.</th>
        <th>Кол-во</th>
        <th class="text-right">Цена</th>
        <th class="text-right">Сумма</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <?php
        $i++;
        $price = $item->product->price * (100 + $model->user->percentage) / 100;
        $priceString = Dashboard::price($price);
        $sum = $item->storeQuantity * $price;
        $total += $sum;
        ?>
        <tr>
            <td><?= $i ?></td>
            <td><?= $item->product->name ?></td>
            <td><?= $item->product->mainUnit ?></td>
            <td><?= $item->storeQuantity ?></td>
            <td class="text-right"><?= Dashboard::price($price) ?> сум</td>
            <td class="text-right"><?= Dashboard::price($sum) ?> сум</td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td></td>
        <td>Итого</td>
        <td colspan="4" class="text-right"><?= Dashboard::price($total) ?> сум</td>
    </tr>
    </tbody>
</table>