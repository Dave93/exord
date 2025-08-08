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

$this->title = "Заказ: #" . $model->id . "; " . $model->store->name . ' на ' . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";

foreach ($categories as $category) {
    if (!UserCategories::isLast($category['id']))
        continue;
    $active = "";
    if ($i == 0) {
        $active = "active";
    }
    $list .= <<<HTML
    <li class="{$active}"><a href="#cat-{$category['id']}" data-toggle="tab">{$category['name']}</a></li>
HTML;

    $itemList = "";
    $items = Products::getProducts($category['id'], $model->id, $user_id);

    foreach ($items as $item) {
        $itemList .= <<<HTML
        <tr>
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td width="200">
                <input type="text" class="form-control" name="Items[{$item['id']}]" value="{$item['quantity']}">
            </td>
        </tr>
HTML;
    }
    $content .= <<<HTML
        <div class="tab-pane {$active}" id="cat-{$category['id']}">
                        <table class="table table-hover table-striped order-table">
                            <thead>
                            <tr>
                                <th>Наименование</th>
                                <th class="text-center">Ед. изм.</th>
                                <th>Количество</th>
                            </tr>
                            </thead>
                            <tbody>
                                {$itemList}
                            </tbody>
                        </table>
                    </div>
HTML;
    $i++;
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
            <?php $form = ActiveForm::begin(); ?>
            <div class="row mb20">
                <div class="col-md-4 col-lg-3">
                    <h4 class="title" style="padding-left: 20px; padding-bottom: 20px;">Категории</h4>
                    <ul class="nav nav-tabs tabs-left">
                        <?= $list ?>
                    </ul>
                </div>

                <div class="col-md-8 col-lg-9">
                    <h4 class="title" style="padding-bottom: 20px">Продукты</h4>
                    <div class="tab-content">
                        <?= $content ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-offset-4 col-lg-offset-3">
                    <div class="form-group">
                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
                    </div>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
