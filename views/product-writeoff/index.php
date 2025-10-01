<?php

use app\models\ProductWriteoff;
use app\models\User;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ProductWriteoffSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Списания продуктов';
$this->params['breadcrumbs'][] = $this->title;

$isAdmin = in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE]);
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Добавить списание', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover',
            ],
            'rowOptions' => function ($model) {
                $class = "";
                if ($model->status == ProductWriteoff::STATUS_NEW)
                    $class = "bg-orange";
                elseif ($model->status == ProductWriteoff::STATUS_APPROVED)
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
                        return Html::a($model->id, ['view', 'id' => $model->id]);
                    },
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'store_name',
                    'label' => 'Магазин',
                    'value' => function ($model) {
                        return $model->store ? $model->store->name : '-';
                    },
                    'visible' => $isAdmin,
                ],
                [
                    'label' => 'Кол. позиций',
                    'value' => function ($model) {
                        return $model->getItemsCount();
                    },
                    'headerOptions' => [
                        'class' => 'text-center'
                    ],
                    'contentOptions' => [
                        'width' => 100,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'created_by',
                    'label' => 'Создал',
                    'value' => function ($model) {
                        return $model->createdBy ? $model->createdBy->fullname : '-';
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'Дата создания',
                    'value' => function ($model) {
                        return date("d.m.Y H:i", strtotime($model->created_at));
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Статус',
                    'value' => function ($model) {
                        return $model->getStatusLabel();
                    },
                    'filter' => [
                        ProductWriteoff::STATUS_NEW => 'Новое',
                        ProductWriteoff::STATUS_APPROVED => 'Утверждено',
                    ],
                    'contentOptions' => [
                        'width' => 100,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'update' => function ($url, $model, $key) {
                            return $model->canEdit() ? Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url) : '';
                        },
                        'delete' => function ($url, $model, $key) use ($isAdmin) {
                            if ($isAdmin && $model->status === ProductWriteoff::STATUS_NEW) {
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                                    'data' => [
                                        'confirm' => 'Вы уверены, что хотите удалить это списание?',
                                        'method' => 'post',
                                    ],
                                ]);
                            }
                            return '';
                        },
                    ],
                    'contentOptions' => [
                        'width' => 80,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
