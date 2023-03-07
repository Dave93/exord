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
    <?php $form = ActiveForm::begin(); ?>
    <input type="hidden" name="orderId" value="<?= $order['id'] ?>">
    <table class="table table-hover table-striped buyer-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Наименование</th>
            <th class="text-nowrap">Ед. изм.</th>
            <th class="text-center">Заказано</th>
            <th class="text-center">Куплено</th>
            <th class="text-center">Цена</th>
            <th class="text-right" width="200">Сумма</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $item): ?>
            <?php $i++; ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $item['name'] ?></td>
                <td class="text-center"><?= $item['mainUnit'] ?></td>
                <td class="text-center"><?= $item['supplierQuantity'] ?></td>
                <td class="quantity w100">
                    <?= Html::textInput("Items[{$item['id']}][purchaseQuantity]", ($item['purchaseQuantity'] != 0) ? $item['purchaseQuantity'] : "", ['class' => 'form-control']) ?>
                </td>
                <td class="price w150"><?= Html::textInput("Items[{$item['id']}][price]", ($item['price'] != 0) ? $item['price'] : "", ['class' => 'form-control']) ?></td>
                <td class="total text-right"></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="6" class="text-right"><strong>Итого: </strong></td>
            <td class="text-right"><strong class="total-sum"></strong></td>
        </tr>
        </tbody>
    </table>
    <hr>
    <h3 class="title">Комментарий</h3>
    <?= Yii::$app->formatter->asNtext($order['comment']) ?>
    <hr>
    <div class="form-group text-right">
        <?= Html::submitButton("Сохранить", ['class' => 'btn btn-success btn-fill']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>