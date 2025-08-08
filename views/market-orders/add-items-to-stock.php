<?php

use app\models\Dashboard;
use app\models\Products;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $categories array */
/* @var $date string */
/* @var $store_id string */

$this->title = "Заказ: " . $model->store->name . ' на ' . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";

$priceClass = '';
if (!Yii::$app->user->identity->showPrice) {
    $priceClass = 'hidden';
}
$folders = Products::getProductParents(Yii::$app->user->id);
foreach ($folders as $folder) {
    $itemList = "";
    $items = Products::getProducts($folder['id'], $model->id, Yii::$app->user->id);
    foreach ($items as $item) {
        $price = $item['price'] * (100 + Yii::$app->user->identity->percentage) / 100;
        $priceString = Dashboard::price($price);

        $itemList .= <<<HTML

        <tr id="p-{$item['id']}">
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td class="text-right {$priceClass} price" data-price="{$price}">{$priceString} сум</td>
            <td width="200">
                <input type="text" class="form-control quantity" name="Items[{$item['id']}]" value="{$item['quantity']}" />
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
                                    <a data-toggle="collapse" href="#collapse-{$folder['id']}">{$folder['name']}</a>
                                </h4>
                            </div>
                            <div id="collapse-{$folder['id']}" class="panel-collapse collapse">
                                <table class="table table-hover table-striped order-table">
                                    <thead>
                                    <tr>
                                        <th>Наименование</th>
                                        <th class="text-center">Ед. изм.</th>
                                        <th class="text-right {$priceClass}">Цена</th>
                                        <th>Количество</th>
                                        <th>В наличии</th>
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
    var availableValue = true;
    let res = true;
    $("input.quantity").each(function(){
        if ($.trim($(this).val()).length != 0){
            isFormValid = true;
        }
    });
    // if ($("input.availability").length > 0) {
    //  //    $("input.availability").each(function(){
    //  //        if ($.trim($(this).val()).length != 0){
    //  //            availableValue = true;
    //  //        }
    //  //    });
    //      $("input.quantity").each(function(){
    //         if ($.trim($(this).val()).length != 0){
    //             const availabilityInput = $(this).closest('tr').find('.availability');
    //             if (availabilityInput.length && ($.trim(availabilityInput.val()).length == 0 || +$.trim(availabilityInput.val()) <= 0)) {
    //                 availableValue = false;
    //             }
    //         }
    //     });
    //    
    //      if(!availableValue){
    //          alert('Пожалуйста укажите текущий остаток товара!');
    //          res = false;
    //      }
    //  }
    if(!isFormValid){
        alert('Пожалуйста выберите продукты для заказа!');
        res = false;
    }
    return res;
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
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <button id="button"></button>
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
            <hr>
            <?= $form->field($model, 'comment')->textarea(['rows' => 7]) ?>
            <hr>
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
                    </div>
                    <div class="col-md-6 text-right">
                        <strong class="total-price <?= $priceClass ?>"></strong>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
