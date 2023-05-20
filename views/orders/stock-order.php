<?php

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

$this->title = "Заказ: " . $model->store->name . ' на ' . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";

$itemList = "";
$items = Products::getProducts(null, $model->id, Yii::$app->user->id);
foreach ($items as $item) {
    $itemList .= <<<HTML
        <tr id="p-{$item['id']}">
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td width="200">
                <input type="text" class="form-control" name="Items[{$item['id']}]" value="{$item['quantity']}">
            </td>
        </tr>
HTML;
}

$js = <<<JS
  $("#searchField").on("keyup", function() {
    let value = $(this).val().toLowerCase();
    $("#products-table tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
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
        <div class="orders-list">
            <?php $form = ActiveForm::begin(); ?>
            <div class="row mb20">
                <div class="col-md-12 col-lg-12">
                    <h4 class="title" style="padding-bottom: 20px">Продукты</h4>
                    <?= Html::textInput('search', null, ['id' => 'searchField', 'class' => 'form-control', 'placeholder' => 'Введите название продукта']) ?>
                    <hr>
                    <div class="tab-content">
                        <table id="products-table" class="table table-hover table-striped order-table">
                            <thead>
                            <tr>
                                <th>Наименование</th>
                                <th class="text-center">Ед. изм.</th>
                                <th>Количество</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?= $itemList ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
