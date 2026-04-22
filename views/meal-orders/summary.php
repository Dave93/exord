<?php

use app\models\Stores;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $rows array */
/* @var $start string */
/* @var $end string */
/* @var $storeId string|null */

$this->title = 'Сводка блюд';
$this->params['breadcrumbs'][] = $this->title;

$storeName = null;
if (!empty($storeId)) {
    $store = Stores::findOne($storeId);
    $storeName = $store ? $store->name : null;
}
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content">
        <?php $form = ActiveForm::begin([
            'id' => 'summaryFilterForm',
            'method' => 'get',
            'action' => ['/meal-orders/summary'],
            'options' => ['data-pjax' => 0],
        ]); ?>
        <div class="row">
            <div class="input-daterange datepicker align-items-center" data-date-format="dd.mm.yyyy"
                 data-today-highlight="1">
                <div class="col-xs-2">
                    <div class="form-group">
                        <label>Дата с</label>
                        <input name="start" class="form-control" placeholder="Дата с" type="text"
                               autocomplete="off" value="<?= Html::encode($start) ?>"/>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="form-group">
                        <label>по</label>
                        <input name="end" class="form-control" placeholder="по" type="text"
                               autocomplete="off" value="<?= Html::encode($end) ?>"/>
                    </div>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="form-group">
                    <label>Филиал</label>
                    <?= Html::dropDownList('storeId', $storeId, Stores::getList(), [
                        'class' => 'selectpicker form-control show-tick',
                        'prompt' => 'Все филиалы',
                        'data-header' => 'Выберите филиал',
                        'data-live-search' => 'true',
                    ]) ?>
                </div>
            </div>
            <div class="col-xs-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <?= Html::submitButton('Показать', ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <hr>

        <div class="row">
            <div class="col-xs-12">
                <p>
                    <strong>Период:</strong>
                    <?= Html::encode($start) ?> &mdash; <?= Html::encode($end) ?>
                    <?php if ($storeName !== null): ?>
                        &nbsp;|&nbsp; <strong>Филиал:</strong> <?= Html::encode($storeName) ?>
                    <?php else: ?>
                        &nbsp;|&nbsp; <strong>Филиал:</strong> Все филиалы
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">№</th>
                        <th>Блюдо</th>
                        <th style="width: 100px;" class="text-center">Ед. изм.</th>
                        <th style="width: 160px;" class="text-right">Кол-во</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Данных за выбранный период нет</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><?= Html::encode($row['dishName']) ?></td>
                                <td class="text-center"><?= Html::encode($row['dishUnit']) ?></td>
                                <td class="text-right"><?= Yii::$app->formatter->asDecimal((float)$row['totalQuantity'], 3) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
