<?php

use app\models\Orders;

/* @var $this yii\web\View */
/* @var $order array */
/* @var $active string */

$products = Orders::getOrderProducts($order['id']);
?>
<div class="tab-pane <?= $active ?>" id="st-<?= $order['id'] ?>">
    <div class="row mb20 p10">
        <div class="col-md-4 text-left"><strong>Заказчик: </strong> <?= Orders::getCustomer($order['id']) ?></div>
        <div class="col-md-4 text-center"><strong>Складчик: </strong> <?= Orders::getStoreMan($order['id']) ?></div>
        <div class="col-md-4 text-right"><strong>Закупщик: </strong> <?= Orders::getSupplier($order['id']) ?></div>
    </div>
    <table class="table table-hover order-table">
        <thead>
        <tr>
            <th>Наименование</th>
            <th class="text-center text-nowrap bg-gray">Заказано</th>
            <th class="text-center text-nowrap bg-blue">Со склада</th>
            <th class="text-center text-nowrap bg-blue">Факт</th>
            <th class="text-center text-nowrap bg-orange">Закуп</th>
            <th class="text-center text-nowrap bg-orange">Куплено</th>
            <th class="text-center text-nowrap bg-orange">Факт</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $item): ?><?php
            $stock = "bg-blue";
            $supplier = "bg-orange";
            if ($item['factStoreQuantity'] != $item['storeQuantity'])
                $stock = "bg-red";
            if ($item['purchaseQuantity'] != $item['factSupplierQuantity'])
                $supplier = "bg-red";
            ?>
            <tr>
                <td><?= $item['name'] ?></td>
                <td class="text-center text-nowrap bg-gray"><?= $item['quantity'] ?></td>
                <td class="text-center text-nowrap <?= $stock ?>"><?= $item['storeQuantity'] ?></td>
                <td class="text-center text-nowrap <?= $stock ?>"><?= $item['factStoreQuantity'] ?></td>
                <td class="text-center text-nowrap <?= $supplier ?>"><?= $item['supplierQuantity'] ?></td>
                <td class="text-center text-nowrap <?= $supplier ?>"><?= $item['purchaseQuantity'] ?></td>
                <td class="text-center text-nowrap <?= $supplier ?>"><?= $item['factSupplierQuantity'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>