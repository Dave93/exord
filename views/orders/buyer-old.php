<?php

use app\models\Orders;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $products array */
/* @var $date string */

$this->title = "Закуп на: " . date("d.m.Y", strtotime($date));
$this->params['breadcrumbs'][] = $this->title;
$i = 0;
$this->registerJs("calcBuyingTotal();");
?>
<div class="orders-list">
    <div class="page-header">
        <h2><?= Html::encode($this->title) ?></h2>
    </div>

    <?php $form = ActiveForm::begin(); ?>
    <?= Html::hiddenInput('date', $date) ?>
    <table class="table table-striped table-hover buyer-table">
        <thead>
        <tr>
            <th class="text-center" width="40">№</th>
            <th>Продукт</th>
            <th class="text-center">Ед. изм.</th>
            <th class="text-center">Кол.</th>
            <th class="text-center">Куплено</th>
            <th class="text-center">Цена</th>
            <th class="text-right" width="200">Сумма</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <?php $i++ ?>
            <tr>
                <td class="text-center" width="40"><?= $i ?></td>
                <td><?= $product['name'] ?></td>
                <td class="text-center"><?= $product['mainUnit'] ?></td>
                <td class="text-center"><?= $product['buyQuantity'] ?></td>
                <td class="quantity w150">
                    <?= Html::hiddenInput("Items[{$product['id']}][quantity]", $product['buyQuantity']) ?>
                    <?= Html::textInput("Items[{$product['id']}][purchaseQuantity]", $product['purchaseQuantity'], ['class' => 'form-control']) ?>
                </td>
                <td class="price w150"><?= Html::textInput("Items[{$product['id']}][price]", $product['price'], ['class' => 'form-control']) ?></td>
                <td class="total text-right"></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="6" class="text-right"><strong>Итого: </strong></td>
            <td class="text-right"><strong class="total-sum"></strong></td>
        </tr>
        </tbody>
    </table>
    <div class="form-group text-right">
        <?= Html::submitButton("Сохранить", ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
