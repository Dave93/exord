<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\OilInventory;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OilInventorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Заполненные записи учета масла';
$this->params['breadcrumbs'][] = ['label' => 'Учет масла', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="oil-inventory-filled">

    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-clipboard"></i> <?= Html::encode($this->title) ?>
                    </h3>
                </div>

                <div class="box-body">

                    <?php Pjax::begin() ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],

                            [
                                'attribute' => 'created_at',
                                'label' => 'Дата создания',
                                'format' => ['date', 'php:d.m.Y H:i'],
                                'filter' => false,
                                'contentOptions' => ['style' => 'width: 130px;'],
                            ],

                            [
                                'attribute' => 'store_id',
                                'label' => 'Магазин',
                                'value' => function ($model) {
                                    return $model->store ? $model->store->name : $model->store_id;
                                },
                                'filter' => false,
                                'contentOptions' => ['style' => 'width: 200px;'],
                            ],

                            [
                                'attribute' => 'return_amount_kg',
                                'label' => 'Возврат (кг)',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return number_format($model->return_amount_kg, 3) . ' кг<br><small class="text-muted">(' . number_format($model->return_amount, 3) . ' л)</small>';
                                },
                                'contentOptions' => ['class' => 'text-right'],
                                'filter' => false,
                            ],

                            [
                                'attribute' => 'status',
                                'label' => 'Статус',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::tag('span', $model->getStatusLabel(), [
                                        'class' => 'label label-warning'
                                    ]);
                                },
                                'filter' => false,
                                'contentOptions' => ['style' => 'width: 100px; text-align: center;'],
                            ],

                            [
                                'class' => 'yii\grid\ActionColumn',
                                'header' => 'Действия',
                                'template' => '{review} {approve} {reject}',
                                'buttons' => [
                                    'approve' => function ($url, $model, $key) {
                                        return Html::a('<i class="fa fa-check"></i>', ['approve', 'id' => $model->id], [
                                            'title' => 'Принять',
                                            'class' => 'btn btn-success btn-xs',
                                            'style' => 'margin-right: 3px;',
                                            'data' => [
                                                'confirm' => 'Вы уверены, что хотите принять эту запись?',
                                                'method' => 'post',
                                            ],
                                        ]);
                                    },
                                    'reject' => function ($url, $model, $key) {
                                        return Html::a('<i class="fa fa-times"></i>', ['reject', 'id' => $model->id], [
                                            'title' => 'Отклонить',
                                            'class' => 'btn btn-danger btn-xs',
                                            'data' => [
                                                'confirm' => 'Вы уверены, что хотите отклонить эту запись?',
                                                'method' => 'post',
                                            ],
                                        ]);
                                    },
                                ],
                                'contentOptions' => ['style' => 'width: 120px; text-align: center;'],
                            ],
                        ],
                    ]); ?>

                    <?php Pjax::end() ?>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.font-weight-bold {
    font-weight: bold !important;
}

.btn-xs {
    padding: 1px 5px;
    font-size: 12px;
    line-height: 1.5;
    border-radius: 3px;
}

.table-hover > tbody > tr:hover > td {
    background-color: #f5f5f5;
}

.alert {
    margin-bottom: 20px;
}
</style> 
