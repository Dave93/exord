<?php

use app\models\Stores;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $detailRows array */
/* @var $summaryRows array */
/* @var $pagination yii\data\Pagination */
/* @var $start string */
/* @var $end string */
/* @var $storeId string|null */
/* @var $allowedStoreIds array|null */

$this->title = 'Цены базара — дашборд';
$this->params['breadcrumbs'][] = ['label' => 'Цены базара', 'url' => ['orders/market-prices']];
$this->params['breadcrumbs'][] = 'Дашборд';

$storeName = null;
if (!empty($storeId)) {
    $store = Stores::findOne($storeId);
    $storeName = $store ? $store->name : null;
}

$formatQty = function ($value) {
    if ($value === null || $value === '') {
        return '-';
    }
    return Yii::$app->formatter->asDecimal((float)$value, 3);
};

$formatMoney = function ($value) {
    if ($value === null || $value === '') {
        return '-';
    }
    return Yii::$app->formatter->asDecimal((float)$value, 2);
};
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content">
        <?php $form = ActiveForm::begin([
            'id' => 'marketPricesDashboardForm',
            'method' => 'get',
            'action' => ['/orders/market-prices-dashboard'],
            'options' => ['data-pjax' => 0],
        ]); ?>
        <div class="row">
            <div class="input-daterange datepicker align-items-center" data-date-format="dd.mm.yyyy"
                 data-today-highlight="1">
                <div class="col-xs-2">
                    <div class="form-group">
                        <label>Дата с</label>
                        <input name="start" class="form-control" placeholder="Дата с" type="text"
                               autocomplete="off" value="<?= Html::encode(date('d.m.Y', strtotime($start))) ?>"/>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="form-group">
                        <label>по</label>
                        <input name="end" class="form-control" placeholder="по" type="text"
                               autocomplete="off" value="<?= Html::encode(date('d.m.Y', strtotime($end))) ?>"/>
                    </div>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="form-group">
                    <label>Филиал</label>
                    <?php
                    $storeOptions = Stores::getList();
                    if (!empty($allowedStoreIds)) {
                        $storeOptions = array_intersect_key($storeOptions, array_flip($allowedStoreIds));
                    }
                    ?>
                    <?= Html::dropDownList('storeId', $storeId, $storeOptions, [
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
                    <?= Html::encode(date('d.m.Y', strtotime($start))) ?>
                    &mdash;
                    <?= Html::encode(date('d.m.Y', strtotime($end))) ?>
                    &nbsp;|&nbsp;
                    <strong>Филиал:</strong>
                    <?= $storeName !== null ? Html::encode($storeName) : 'Все филиалы' ?>
                </p>
            </div>
        </div>

        <h4>Детализация закупок</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 110px;" class="text-center">Дата</th>
                        <th>Филиал</th>
                        <th>Продукт</th>
                        <th style="width: 150px;" class="text-right">Кол-во</th>
                        <th style="width: 170px;" class="text-right">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detailRows)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Данных за выбранный период нет</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detailRows as $row): ?>
                            <tr>
                                <td class="text-center"><?= Html::encode(date('d.m.Y', strtotime($row['orderDate']))) ?></td>
                                <td><?= Html::encode($row['storeName'] ?: '-') ?></td>
                                <td>
                                    <?= Html::encode($row['productName']) ?>
                                    <?php if (!empty($row['productUnit'])): ?>
                                        <small class="text-muted">(<?= Html::encode($row['productUnit']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><?= $formatQty($row['market_total_quantity']) ?></td>
                                <td class="text-right"><?= $formatMoney($row['market_total_price']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination->totalCount > $pagination->pageSize): ?>
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'pagination'],
            ]) ?>
        <?php endif; ?>

        <h4 style="margin-top: 30px;">Сводка по продуктам</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">№</th>
                        <th>Продукт</th>
                        <th style="width: 100px;" class="text-center">Ед. изм.</th>
                        <th style="width: 160px;" class="text-right">Общее кол-во</th>
                        <th style="width: 180px;" class="text-right">Общая сумма</th>
                        <th style="width: 200px;" class="text-right">Средняя сумма за ед.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summaryRows)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Данных за выбранный период нет</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summaryRows as $i => $row):
                            $totalQty = $row['totalQuantity'] !== null ? (float)$row['totalQuantity'] : 0.0;
                            $totalSum = $row['totalPrice'] !== null ? (float)$row['totalPrice'] : 0.0;
                            $avgPerUnit = $totalQty > 0 ? $totalSum / $totalQty : null;
                        ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><?= Html::encode($row['productName']) ?></td>
                                <td class="text-center"><?= Html::encode($row['productUnit']) ?></td>
                                <td class="text-right"><?= $formatQty($row['totalQuantity']) ?></td>
                                <td class="text-right"><?= $formatMoney($row['totalPrice']) ?></td>
                                <td class="text-right"><?= $avgPerUnit !== null ? Yii::$app->formatter->asDecimal($avgPerUnit, 2) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
