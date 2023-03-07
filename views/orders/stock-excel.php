<?php

use app\models\Orders;
use app\models\Products;
use app\models\UserCategories;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $orders array */
/* @var $products array */
$groupId = null;
$ordersCount = count($orders);
?>
<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
<table border="1">
    <thead>
    <tr>
        <th>Наименование</th>
        <?php foreach ($orders as $order): ?>
            <th><?= $order['name'] ?></th>
        <?php endforeach; ?>
        <th>Итого</th>
    </tr>
    <tr>
        <th></th>
        <?php foreach ($orders as $order): ?>
            <th><?= $order['id'] ?></th>
        <?php endforeach; ?>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $key => $product): ?>
        <?php if ($groupId != $product['parentId']): ?>
            <tr>
                <td style="font-weight: bold; background: yellow"><?= $product['parentName'] ?></td>
                <td colspan="<?= $ordersCount + 1 ?>"></td>
            </tr>
            <?php $groupId = $product['parentId'] ?>
        <?php endif; ?>
        <tr>
            <td><?= $product['name'] ?></td>
            <?php $total = 0; ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $quantity = 0;
                if (isset($product[$order['id']]) && !empty($product[$order['id']])) {
                    $quantity = $product[$order['id']];
                }
                $total += $quantity;
                ?>
                <th><?= $quantity ?></th>
            <?php endforeach; ?>
            <td><?= $total ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>