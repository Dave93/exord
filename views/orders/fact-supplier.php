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

$this->title = "Приход от поставщика на заказ: " . $model->id;
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$items = OrderItems::getSupplierItemsList($model->id);
foreach ($items as $item) {
    $i++;
    $quantity = ($item['factSupplierQuantity'] != 0) ? $item['factSupplierQuantity'] : "";
    $list .= <<<HTML
        <tr>
            <td width="40">{$i}</td>
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td class="text-center">{$item['supplierQuantity']}</td>
            <td class="text-center">{$item['purchaseQuantity']}</td>
            <td width="200">
                <input type="text" class="form-control" name="Items[{$item['productId']}]" value="{$quantity}">
            </td>
        </tr>
HTML;
}
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="orders-list">
            <?php if (!empty($list)): ?>
                <?php $form = ActiveForm::begin(); ?>
                <div class="tab-content">
                    <table class="table table-hover table-striped order-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Наименование</th>
                            <th class="text-center">Ед. изм.</th>
                            <th class="text-center">Заказано</th>
                            <th class="text-center">Отправлено</th>
                            <th>Получено</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?= $list ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-group">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            <?php else: ?>
                <p class="warning alert alert-danger">Вашего заказа еще не подтвердил Поставщик</p>
            <?php endif; ?>
        </div>
    </div>
</div>
