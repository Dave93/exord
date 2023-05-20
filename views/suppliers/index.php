<?php

use app\models\Dashboard;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SupplierSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Поставщики';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-striped table-hover',
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 40,
                    ]
                ],
//            'id',
                'name',
                [
                    'attribute' => 'client',
                    'filter' => Dashboard::$yesNo,
                    'value' => function ($model) {
                        return Dashboard::$yesNo[$model->client];
                    },
                    'contentOptions' => [
                        'class' => 'text-center'
                    ]
                ],
//                [
//                    'attribute' => 'deleted',
//                    'filter' => Dashboard::$yesNo,
//                    'value' => function ($model) {
//                        return Dashboard::$yesNo[$model->deleted];
//                    },
//                    'contentOptions' => [
//                        'class' => 'text-center'
//                    ]
//                ],
//                [
//                    'attribute' => 'supplier',
//                    'filter' => Dashboard::$yesNo,
//                    'value' => function ($model) {
//                        return Dashboard::$yesNo[$model->supplier];
//                    },
//                    'contentOptions' => [
//                        'class' => 'text-center'
//                    ]
//                ],
//                [
//                    'attribute' => 'employee',
//                    'filter' => Dashboard::$yesNo,
//                    'value' => function ($model) {
//                        return Dashboard::$yesNo[$model->employee];
//                    },
//                    'contentOptions' => [
//                        'class' => 'text-center'
//                    ]
//                ],
                [
                    'attribute' => 'syncDate',
                    'value' => function ($model) {
                        return date("d.m.Y H:i", strtotime($model->syncDate));
                    },
                    'headerOptions' => [
                        'class' => 'text-right'
                    ],
                    'contentOptions' => [
                        'width' => 140,
                        'class' => 'text-right'
                    ]
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update}',
                    'contentOptions' => [
                        'width' => 20,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
