<?php

use app\models\Dashboard;
use app\models\Orders;
use app\models\Products;
use app\models\UserCategories;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $categories array */
/* @var $date string */
/* @var $store_id string */
/* @var $user_id int */

$this->title = "Заказ: #" . $model->id . "; " . $model->store->name . ' на ' . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";

$priceClass = '';
if (!Yii::$app->user->identity->showPrice) {
    $priceClass = 'hidden';
}

$folders = Products::getProductParents($model->userId);
foreach ($folders as $folder) {
    $itemList = "";
    $items = Products::getProducts($folder['id'], $model->id, $model->userId);
    foreach ($items as $item) {
        $price = $item['price'] * (100 + Yii::$app->user->identity->percentage) / 100;
        $priceString = Dashboard::price($price);
        $itemList .= <<<HTML
        <tr id="p-{$item['id']}">
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td class="text-right {$priceClass} price" data-price="{$price}">{$priceString} сум</td>
            <td width="200">
                <input type="text" class="form-control quantity" name="Items[{$item['id']}]" value="{$item['quantity']}">
            </td>
        </tr>
HTML;
    }
    if (empty($itemList))
        continue;
    $list .= <<<LIST
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{$folder['id']}">{$folder['name']}</a>
                                </h4>
                            </div>
                            <div id="collapse-{$folder['id']}" class="panel-collapse collapse">
                                <table class="table table-hover table-striped order-table products-table">
                                    <thead>
                                    <tr>
                                        <th>Наименование</th>
                                        <th class="text-center">Ед. изм.</th>
                                        <th class="text-right {$priceClass}">Цена</th>
                                        <th>Количество</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        {$itemList}
                                    </tbody>
                                </table>    
                            </div>
                        </div>
LIST;
}

$js = <<<JS
$("#searchField").on("keyup", function() {
    let value = $(this).val().toLowerCase();
    
    $(".order-table tr").filter(function() {
        $(this).toggle($(this).find('td:first').text().toLowerCase().includes(value));
    }).promise().done(function() {
        $('div.panel-collapse').removeClass('in');
        if(value.length==0)
            return;
        $('.order-table tr:not([style*="display: none"])').closest('div.panel-collapse').css('height','auto').addClass('in').attr('aria-expanded','true');
    });
  });

$('#order-form').on('beforeSubmit',function(e){
    var isFormValid = false;
    $("input.quantity").each(function(){
        if ($.trim($(this).val()).length != 0){
            isFormValid = true;
        }
    });
    if(!isFormValid){
        alert('Пожалуйста выберите продукты для заказа!');
        return false;
    }
    return true;
});
$(document).on("keydown", "#order-form", function(event) {
    return event.keyCode != 13;
});
$('body').on('keyup','input.quantity',function(){
    calculateOrderTotal();
});
function calculateOrderTotal() {
      let total = 0;
      $("table.order-table tr").each(function () {
        let sum = 0;
        let quantity = $(this).find("input.quantity").val();
        let price = $(this).find("td.price").attr('data-price');
        if (!quantity)
            quantity = 0;
        if (!price)
            price = 0;
        if (parseFloat(quantity) && parseFloat(price)) {
            sum = parseFloat(quantity) * parseFloat(price);
            // $(this).find("td.total").text(formatPrice(sum) + " сум");
            total += sum;
        }
    });
    $(".total-price").text(formatPrice(total) + " сум");
}
JS;
$this->registerJs($js);
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', ['orders/customer-orders'], ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="orders-list">
            <h4 class="title" style="padding-bottom: 20px">Продукты</h4>
            <?= Html::textInput('search', null, ['id' => 'searchField', 'class' => 'form-control', 'placeholder' => 'Введите название продукта']) ?>
            <hr>
            <?php $form = ActiveForm::begin([
                'id' => 'order-form'
            ]); ?>
            <div class="panel-group" id="accordion">
                <?= $list ?>
            </div>
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
