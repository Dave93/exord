<?php

use app\models\Orders;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $categories array */

$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime($today . " +1 day"));
$this->title = "Заказ: " . $model->establishment->name . ' на ' . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;
$category = "";
?>
<div class="orders-list">
    <div class="page-header clearfix">
        <div class="pull-left">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="pull-right">
            <?= Html::a('Добавить на ' . date("d.m.Y", strtotime($yesterday)), ['create'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?php $form = ActiveForm::begin(); ?>

    <?= Html::hiddenInput('establishment', $model->establishment_id) ?>
    <?= Html::hiddenInput('date', $model->date) ?>
    <div class="row">
        <div class="col-md-4 col-lg-3">
            <h4 class="tab-block-title">Категории</h4>
            <ul class="nav nav-tabs tabs-left">
                <?php $i = 0; ?>
                <?php foreach ($categories as $category): ?>
                    <li class="<?= ($i == 0) ? 'active' : '' ?>">
                        <a href="#est-<?= $category['id'] ?>" data-toggle="tab"><?= $category['name'] ?></a></li>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-8 col-lg-9">
            <h4 class="tab-block-title">Продукты</h4>
            <div class="tab-content">
                <?php $i = 0; ?>
                <?php foreach ($categories as $category): ?>
                    <?php $items = Orders::getItemsByCategory($model->establishment_id, $model->date, $category['id']); ?>
                    <div class="tab-pane <?= ($i == 0) ? 'active' : '' ?>" id="est-<?= $category['id'] ?>">
                        <table class="table table-hover order-table">
                            <thead>
                            <tr>
                                <th>Наименование</th>
                                <th>Ед. изм.</th>
                                <th>Количество</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= $item['name'] ?></td>
                                    <td><?= $item['measureUnit'] ?></td>
                                    <td width="200">
                                        <?= Html::hiddenInput("Items[{$i}][id]", $item['id']) ?>
                                        <?= Html::textInput("Items[{$i}][quantity]", $item['quantity'], ['class' => 'form-control']) ?>
                                    </td>
                                </tr>
                                <?php $i++; ?>
                            <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
