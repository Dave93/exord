<?php

use app\models\Dashboard;
use app\models\Suppliers;
use kartik\date\DatePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $start string */
/* @var $end string */
/* @var $product string */
/* @var $supplier string */
/* @var $data array */
/* @var $mean array */
/* @var $suppliers array */
/* @var $searchModel app\models\SupplierSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Изменение цен';
$this->params['breadcrumbs'][] = $this->title;

$charTitle = '';
$charData = '';
$dates = array_keys($data);
$ss = ArrayHelper::map(Suppliers::find()->asArray()->all(), 'id', 'name');
foreach ($dates as $d) {
    $m = $mean[$d];
    $d = date('d.m.y', strtotime($d));
    $charTitle .= "'{$d}',";
    $charData .= "'{$m}',";
}
if (!empty($charTitle))
    $charTitle = substr($charTitle, 0, -1);
if (!empty($charData))
    $charData = substr($charData, 0, -1);
?>
    <div class="card">
        <div class="header clearfix">
            <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
            <div class="pull-right">
                <?= Html::a("Excel", ['report/price-excel', 'start' => $start, 'end' => $end, 'product' => $product], [
                    'class' => 'btn btn-success btn-fill',
                ]) ?>
            </div>
        </div>
        <hr>
        <div class="content">
            <div class="row">
                <?php $form = ActiveForm::begin([
                    'id' => 'filterForm',
                    'method' => 'get',
                    'action' => ['report/price']
                ]); ?>
                <div class="col-md-2 mb20">
                    <?= DatePicker::widget([
                        'name' => 'start',
                        'removeButton' => false,
                        'value' => $start,
                        'pluginOptions' => [
                            'todayHighlight' => true,
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd'
                        ],
                        'options' => [
                            'autocomplete' => 'off',
                            'placeholder' => 'Дата с',
                        ]
                    ]); ?>
                </div>
                <div class="col-md-2 mb20">
                    <?= DatePicker::widget([
                        'name' => 'end',
                        'removeButton' => false,
                        'value' => $end,
                        'pluginOptions' => [
                            'todayHighlight' => true,
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd'
                        ],
                        'options' => [
                            'autocomplete' => 'off',
                            'placeholder' => 'по',
                        ]
                    ]); ?>
                </div>
                <div class="col-md-2 mb20">
                    <?= Html::dropDownList('product', $product, Dashboard::getIncomingProducts(), ['class' => 'selectpicker form-control show-tick', 'prompt' => 'Выберите продукт', 'data-header' => "Выберите продукт", 'data-live-search' => 'true']) ?>
                </div>
                <div class="col-md-2 mb20">
                    <?= Html::submitButton('Показать', ['class' => 'btn btn-primary btn-fill']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
            <hr>

            <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th class="text-nowrap">Наименование</th>
                            <?php foreach ($dates as $d): ?>
                                <th class="text-nowrap"><?= date('d.m.y', strtotime($d)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td class="text-nowrap"><?= $ss[$s] ?></td>
                                <?php foreach ($dates as $d): ?>
                                    <td class="text-nowrap"><?= Dashboard::clearPrice($data[$d][$s]) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="text-nowrap">Среднее значение</td>
                            <?php foreach ($dates as $d): ?>
                                <td class="text-nowrap"><?= Dashboard::clearPrice($mean[$d]) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <hr>
                <canvas id="bar-chart"></canvas>
                <canvas id="line-chart"></canvas>
            <?php else: ?>
                <p>Ничего не найдено</p>
            <?php endif; ?>
        </div>
    </div>
<?php
$js = <<<JS
var chartColors = {
    red: 'rgb(255, 99, 132)',
    orange: 'rgb(255, 159, 64)',
    yellow: 'rgb(255, 205, 86)',
    green: 'rgb(75, 192, 192)',
    blue: 'rgb(54, 162, 235)',
    purple: 'rgb(153, 102, 255)',
    grey: 'rgb(201, 203, 207)',
    black: 'rgb(0, 0, 0)',
    aqua: 'rgb(0, 255, 255)',
    navy: 'rgb(0, 0, 128)',
};

var barChart = document.getElementById('bar-chart').getContext('2d');
var myBar = new Chart(barChart, {
    type: 'bar',
    data: {
        labels: [{$charTitle}],
        datasets: [
            {
                label: 'Сумма',
                backgroundColor: chartColors.red,
                borderColor: chartColors.red,
                borderWidth: 0,
                data: [{$charData}]
            }
        ]
    
    },
    options: {
        responsive: true,
        legend: {
            position: 'top',
        },
        title: {
            display: false,
            text: 'Сумма'
        },
        scales: {
            xAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'Дни'
                },
            }],
            yAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'Сумма'
                }
            }]
        }
    }
});

var chart = new Chart('line-chart', {
    type: 'line',
    data: {
        labels: [{$charTitle}],
        datasets: [{
            label: 'Продажа',
            backgroundColor: chartColors.purple,
            borderColor: chartColors.purple,
            data: [{$charData}],
            fill: false
        }]
    },
    options: {
        responsive: true,
        elements: {
            line: {
                tension: 0.000001
            }
        },
        title: {
            display: false,
            text: ''
        },
        tooltips: {
            mode: 'index',
            intersect: false,
        },
        hover: {
            mode: 'nearest',
            intersect: true
        },
        scales: {
            xAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'Месяц'
                },
            }],
            yAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'Количество'
                }
            }]
        }
    }
});
JS;

$this->registerJs($js);