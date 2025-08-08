<?php

use app\models\OrderItems;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $categories array */
/* @var $date string */
/* @var $store_id string */
/* @var $user_id int */

$this->title = "Приход из склада: " . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;

$list = "";
$items = OrderItems::getStockItemsList($model->id);
foreach ($items as $item) {
    $quantity = ($item['factStoreQuantity'] != 0) ? $item['factStoreQuantity'] : "";
    $list .= <<<HTML
        <tr>
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td width="200">
                <input type="text" class="form-control" name="Items[{$item['productId']}]" value="{$quantity}">
            </td>
        </tr>
HTML;
}
?>
<div class="orders-list">
    <div class="page-header">
        <h2><?= Html::encode($this->title) ?></h2>
    </div>
    <?php $form = ActiveForm::begin(); ?>
    <div class="tab-content">
        <table class="table table-hover order-table">
            <thead>
            <tr>
                <th>Наименование</th>
                <th class="text-center">Ед. изм.</th>
                <th>Количество</th>
            </tr>
            </thead>
            <tbody>
            <?= $list ?>
            </tbody>
        </table>
    </div>
    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
