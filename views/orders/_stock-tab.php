<?php

use app\models\Orders;
use app\models\User;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $order array */
/* @var $active string */

$i = 0;
$groupedProducts = [];
$storeId = null;
$supplierId = null;
uasort($products, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});
foreach ($products as $product) {
    if (empty($groupedProducts[$product['groupName']])) {
        $groupedProducts[$product['groupName']] = [
            'name' => $product['groupName'],
            'products' => []
        ];
    }
    $groupedProducts[$product['groupName']]['products'][] = $product;
    $storeId = $product['storeId'];
    $supplierId = $product['supplierId'];
}

// sort by name
//echo '<pre>'; print_r($groupedProducts); echo '</pre>';

uasort($groupedProducts, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>
<div class="tab-pane <?= $active ?>" id="st-<?= $order['id'] ?>">
    <?php $form = ActiveForm::begin(); ?>
    <div class="flex justify-between">
        <?php if (Yii::$app->user->identity->username != 'Abbos' && Yii::$app->user->identity->role != User::ROLE_ETAJ && $order['state'] == 0) { ?>
            <?= Html::submitButton("Отправить", ['class' => 'btn btn-success btn-fill', 'name' => 'send', 'value' => 'Y']) ?>
        <?php } ?>
        <div class="text-right">
            <?php if (Yii::$app->user->identity->role == User::ROLE_ADMIN) { ?>
                <?= Html::a('<i class="glyphicon glyphicon-plus"></i> Добавить', ['orders/add-items-to-stock', 'orderId' => $order['id'], 'storeId' => $storeId, 'supplierId' => $supplierId], ['class' => 'btn btn-primary btn-fill']) ?>
            <?php } ?>
            <?= Html::a('<i class="glyphicon glyphicon-download-alt"></i> Накладной', ['orders/invoice', 'id' => $order['id']], ['class' => 'btn btn-warning btn-fill']) ?>
            <?= Html::a('<i class="glyphicon glyphicon-download-alt"></i> Загрузить', ['order-excel', 'id' => $order['id']], ['class' => 'btn btn-success btn-fill order-excel']) ?>
        </div>
    </div>
    <input type="hidden" name="orderId" value="<?= $order['id'] ?>">

    <?php foreach ($groupedProducts as $group) { ?>
        <h3><?= $group['name'] ? $group['name'] : 'Остальные продукты' ?></h3>
        <table class="table table-hover table-striped order-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Наименование</th>
                <th>Ед. изм.</th>
                <th class="text-center">Заказано</th>
                <th>Действие</th>
                <th class="text-center">Со склада</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($group['products'] as $item): ?>
                <?php
                $i++;
                if ($item['minused'] == 1) {

                    $fromStock = $item['storeQuantity'];
                } else {

                    $fromStock = ($item['storeQuantity'] != 0) ? $item['storeQuantity'] : $item['quantity'];
                }
                $fromSupplier = ($item['supplierQuantity'] != null && $item['supplierQuantity'] != '') ? $item['supplierQuantity'] : $item['quantity'] - $fromStock; ?>
                <tr <?php if ($item['prepared'] == 1) { ?> class="prepared" <?php } ?>>
                    <td><?= $i ?></td>
                    <td><?= $item['name'] ?></td>
                    <td><?= $item['mainUnit'] ?></td>
                    <td class="order_quantity text-center"><?= $item['quantity'] ?></td>
                    <td>
                        <?php
                        // check for etaj role
                        if (Yii::$app->user->identity->role == User::ROLE_ETAJ) {
                            echo Html::a('<i class="glyphicon glyphicon-minus"></i>', ['orders/minus-from-stock', 'orderId' => $order['id'], 'itemId' => $item['productId']], ['class' => 'btn btn-danger mr-1 btn-fill btn-xs']);
                        }
                        ?>
                    </td>
                    <td width="100">
                        <input type="text" class="from_stock form-control" name="Items[<?= $item['id'] ?>][s]"
                               value="<?= $fromStock ?>">
                    </td>
                    <td>
                        <?php
                        // check for etaj role
                        if (Yii::$app->user->identity->role == User::ROLE_ETAJ && $item['prepared'] == 0) {
                            echo Html::a('<i class="glyphicon glyphicon-ok"></i>', ['orders/prepared-from-stock', 'orderId' => $order['id'], 'itemId' => $item['productId']], ['class' => 'btn btn-success mr-1 btn-fill btn-xs']);
                        }
                        // check for admin role
                        if (Yii::$app->user->identity->role == User::ROLE_ADMIN || Yii::$app->user->identity->username == 'Abbos') {
                            echo Html::a('<i class="glyphicon glyphicon-remove"></i>', ['orders/delete-from-stock', 'orderId' => $order['id'], 'itemId' => $item['productId']], ['class' => 'btn btn-danger btn-fill btn-xs']);
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php } ?>


    <hr>
    <h3 class="title">Комментарий</h3>
    <textarea name="comment" type="text" class="form-control"></textarea>
    <hr>
    <div class="form-group text-right flex justify-between mt-20">
        <?php /* if ($order['state'] == 0): ?>
            <?= Html::a("Отправить", ['orders/send', 'id' => $order['id']], ['class' => 'btn btn-warning btn-fill', 'data-pjax' => '0', 'data-confirm' => 'Вы уверены, что хотите отправить этот заказ?']) ?>
        <?php endif; */ ?>
        <?php if (Yii::$app->user->identity->role == User::ROLE_ADMIN) { ?>
            <?= Html::submitButton("Удалить", ['class' => 'btn btn-danger btn-fill', 'name' => 'delete', 'value' => 'Y']) ?>
        <?php } ?>

        <?php if (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) { ?>
                <?= Html::button('<i class="glyphicon glyphicon-time"></i> История изменений', [
                    'class' => 'btn btn-info btn-fill show-changelog-modal',
                    'data-order-id' => $order['id']
                ]) ?>
            <?php } ?>
        <div>
            <?= Html::submitButton("Сохранить", ['class' => 'btn btn-primary mr-5 btn-fill', 'name' => 'save', 'value' => 'Y']) ?>
            
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>