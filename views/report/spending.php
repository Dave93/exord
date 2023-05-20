<?php

use app\models\Dashboard;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $start string */
/* @var $end string */
/* @var $coefficient string */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = 'Расходы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <div class="pull-right">
            <?= Html::a("Excel", ['report/spending-excel', 'start' => $start, 'end' => $end, 'coefficient' => $coefficient], [
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
                'action' => ['report/spending']
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
                <?= Html::textInput('coefficient', $coefficient, ['class' => 'form-control', 'placeholder' => 'Коэффициент']) ?>
            </div>
            <div class="col-md-2 mb20">
                <?= Html::submitButton('Показать', ['class' => 'btn btn-primary btn-fill']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        <hr>

        <div class="table-responsive mb20">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'summary' => false,
                'tableOptions' => [
                    'class' => 'table table-hover table-striped',
                ],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'contentOptions' => [
                            'width' => 40,
                        ]
                    ],
                    [
                        'label' => 'Название',
                        'attribute' => 'name',
                    ],
                    [
                        'label' => 'Ед. Изм.',
                        'attribute' => 'unit',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'label' => 'Расход',
                        'attribute' => 'total',
                        'value' => function ($model) {
                            return Dashboard::price($model['total']);
                        },
                        'headerOptions' => [
                            'class' => 'text-right'
                        ],
                        'contentOptions' => [
                            'class' => 'text-right'
                        ]
                    ],
                    [
                        'label' => 'Остаток',
                        'attribute' => 'stock',
                        'value' => function ($model) {
                            return Dashboard::price($model['stock']);
                        },
                        'headerOptions' => [
                            'class' => 'text-right'
                        ],
                        'contentOptions' => [
                            'class' => 'text-right'
                        ]
                    ],
                    [
                        'label' => 'Закуп',
                        'value' => function ($model) use ($coefficient) {
                            if ($model['stock'] > $model['total'] * $coefficient) {
                                return '0';
                            }
                            return round(($model['total'] * $coefficient - $model['stock']));
                        },
                        'headerOptions' => [
                            'class' => 'text-right'
                        ],
                        'contentOptions' => [
                            'class' => 'text-right'
                        ]
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>