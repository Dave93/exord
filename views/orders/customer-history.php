<?php

use app\models\Dashboard;
use app\models\Orders;
use app\models\User;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Заказы';
$this->params['breadcrumbs'][] = $this->title;
$isOrderMan = Dashboard::isOrderMan();
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover',
            ],
            'rowOptions' => function ($model) {
                $class = "";
                if ($model->state == 1)
                    $class = "bg-orange";
                elseif ($model->state == 2)
                    $class = "bg-green";
                return ['class' => $class];
            },
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 40,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'id',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->id, ['orders/view', 'id' => $model->id]);
                    }
                ],
                [
                    'label' => 'Кол. позиц.',
                    'value' => function ($model) {
                        return count($model->items);
                    },
                    'headerOptions' => [
                        'class' => 'text-center'
                    ],
                    'contentOptions' => [
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'date',
                    'value' => function ($model) {
                        return date("d.m.Y", strtotime($model->date));
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'addDate',
                    'value' => function ($model) {
                        return date("d.m.Y H:i", strtotime($model->addDate));
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'state',
                    'value' => function ($model) {
                        return Orders::$states[$model->state];
                    },
                    'contentOptions' => [
                        'width' => 100,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view}',
                    'contentOptions' => [
                        'width' => 40,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
