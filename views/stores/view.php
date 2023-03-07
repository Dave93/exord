<?php

use app\models\Dashboard;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Stores */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Склады', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content">
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
                        'label' => 'Количество',
                        'attribute' => 'amount',
                        'value' => function ($model) {
                            return Dashboard::price($model['amount']);
                        },
                        'headerOptions' => [
                            'class' => 'text-right'
                        ],
                        'contentOptions' => [
                            'class' => 'text-right'
                        ]
                    ],
                    [
                        'label' => 'Сумма',
                        'attribute' => 'sum',
                        'value' => function ($model) {
                            return Dashboard::price($model['sum']);
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