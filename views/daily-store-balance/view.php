<?php

use app\models\Dashboard;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\DailyStoreBalance */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = $model->store_name;
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
            <div>
                Дата синхронизации <?=date('d.m.Y H:i:s', strtotime($model->created_at))?>
            </div>
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
                        'label' => 'Остаток на начало дня (iiko)',
                        'attribute' => 'quantity',
                        'value' => function ($model) {
                            return Dashboard::price($model['quantity']);
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