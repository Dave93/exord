<?php

use app\models\Orders;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $order array */
/* @var $active string */

$i = 0;
?>
<div class="tab-pane <?= $active ?>" id="st-<?= $order['id'] ?>">
    <div class="text-right">
        <?= Html::a('<i class="glyphicon glyphicon-download-alt"></i> Накладной', ['orders/invoice', 'id' => $order['id']], ['class' => 'btn btn-warning btn-fill']) ?>
        <?= Html::a('<i class="glyphicon glyphicon-download-alt"></i> Загрузить', ['order-excel', 'id' => $order['id']], ['class' => 'btn btn-success btn-fill']) ?>
    </div>
    <?php $form = ActiveForm::begin(); ?>
    <input type="hidden" name="orderId" value="<?= $order['id'] ?>">
    <table class="table table-hover table-striped order-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Наименование</th>
            <th>Ед. изм.</th>
            <th class="text-center">Заказано</th>
            <th class="text-center">Со склада</th>
            <th class="text-right">Закуп</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $item): ?>
            <?php
            $i++;
            $fromStock = ($item['storeQuantity'] != 0) ? $item['storeQuantity'] : 0;
            $fromSupplier = ($item['supplierQuantity'] != null && $item['supplierQuantity'] != '') ? $item['supplierQuantity'] : $item['quantity'] - $fromStock; ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $item['name'] ?></td>
                <td><?= $item['mainUnit'] ?></td>
                <td class="order_quantity text-center"><?= $item['quantity'] ?></td>
                <td width="100">
                    <input type="text" class="from_stock form-control" name="Items[<?= $item['id'] ?>][s]"
                           value="<?= $fromStock ?>">
                </td>
                <td width="100">
                    <input type="text" class="to-buy form-control" name="Items[<?= $item['id'] ?>][b]"
                           value="<?= $fromSupplier ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <h3 class="title">Комментарий</h3>
    <?= Yii::$app->formatter->asNtext($order['comment']) ?>
    <hr>
    <div class="form-group text-right">
        <?php /* if ($order['state'] == 0): ?>
            <?= Html::a("Отправить", ['orders/send', 'id' => $order['id']], ['class' => 'btn btn-warning btn-fill', 'data-pjax' => '0', 'data-confirm' => 'Вы уверены, что хотите отправить этот заказ?']) ?>
        <?php endif; */?>
        <?= Html::submitButton("Отправить", ['class' => 'btn btn-success btn-fill']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>