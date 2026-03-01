<?php

use app\models\Dashboard;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\DishSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Блюда';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover table-striped',
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 40,
                        'class' => 'text-center'
                    ]
                ],
                'name',
                'unit',
                [
                    'attribute' => 'active',
                    'filter' => [0 => 'Нет', 1 => 'Да'],
                    'value' => function ($model) {
                        return $model->active ? 'Да' : 'Нет';
                    },
                    'contentOptions' => [
                        'class' => 'text-center',
                        'width' => 80,
                    ],
                    'headerOptions' => [
                        'class' => 'text-center'
                    ]
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update} {delete}',
                    'contentOptions' => [
                        'width' => 80,
                        'class' => 'text-center'
                    ],
                    'buttons' => [
                        'delete' => function ($url, $model) {
                            if ($model->active) {
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                                    'title' => 'Деактивировать',
                                    'data-confirm' => 'Вы уверены, что хотите деактивировать это блюдо?',
                                    'data-method' => 'post',
                                ]);
                            }
                            return '';
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
