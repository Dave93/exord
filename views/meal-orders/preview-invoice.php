<?php

use app\models\MealOrderItems;
use app\models\MealOrders;

/* @var $this yii\web\View */
/* @var $model MealOrders */

$i = 0;
$items = MealOrderItems::find()->where(['mealOrderId' => $model->id])->all();
?>
<div style="font-size: 20px; font-weight: bold">ЗАКАЗ БЛЮД</div>
<div>Номер заказа: <?= $model->id ?></div>
<div>Дата заказа: <?= date('d.m.Y', strtotime($model->date)) ?></div>
<div>Заказчик: <?= $model->user->username ?></div>
<div>Филиал: <?= $model->store ? $model->store->name : 'Не указан' ?></div>
<div>Комментарий: <?= ($model->comment ? $model->comment : '—') ?></div>

<br>

<table border="1" width="100%" cellpadding="4" cellspacing="0">
    <thead>
    <tr>
        <th width="30">№</th>
        <th align="center">Блюдо</th>
        <th width="100" align="center">Кол-во</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <?php $i++; ?>
        <tr>
            <td width="30" align="center"><?= $i ?></td>
            <td><?= $item->dish ? $item->dish->name : 'Блюдо #' . $item->dishId ?></td>
            <td width="100" align="center"><?= $item->quantity ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
