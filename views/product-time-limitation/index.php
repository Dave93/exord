<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Ограничения по времени для продуктов';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <div class="pull-right">
            <?= Html::a('Создать ограничение', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
        </div>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="product-time-limitation-index">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'attribute' => 'productId',
                        'value' => function ($model) {
                            return $model->product->name;
                        },
                        'label' => 'Продукт'
                    ],
                    [
                        'attribute' => 'startTime',
                        'format' => ['time', 'php:H:i'],
                        'label' => 'Время начала'
                    ],
                    [
                        'attribute' => 'endTime',
                        'format' => ['time', 'php:H:i'],
                        'label' => 'Время окончания'
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                $url = ['product-time-limitation/view', 'id' => $model->productId];
                                return Html::a('<i class="fa fa-eye"></i>', $url, [
                                    'title' => 'Просмотр',
                                    'data-pjax' => '0',
                                ]);
                            },
                            'update' => function ($url, $model) {
                                $url = ['product-time-limitation/update', 'productId' => $model->productId];
                                return Html::a('<i class="fa fa-pencil"></i>', $url, [
                                    'title' => 'Редактировать',
                                    'data-pjax' => '0',
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fa fa-trash"></i>', $url, [
                                    'title' => 'Удалить',
                                    'data-confirm' => 'Вы уверены, что хотите удалить этот элемент?',
                                    'data-method' => 'post',
                                    'data-pjax' => '0',
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div> 