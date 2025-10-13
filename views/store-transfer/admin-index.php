<?php

use app\models\StoreTransfer;
use yii\helpers\Html;
use yii\grid\GridView;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel app\models\StoreTransferSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Управление внутренними перемещениями';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card">
    <div class="header">
        <h4 class="title"><?= Html::encode($this->title) ?></h4>
        <p class="category">Все заявки на перемещение продуктов между филиалами</p>
    </div>
    <div class="content">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class' => 'table table-hover table-striped'],
            'columns' => [
                [
                    'attribute' => 'id',
                    'headerOptions' => ['style' => 'width: 80px;'],
                ],
                [
                    'attribute' => 'request_store_name',
                    'label' => 'Филиал-заказчик',
                    'value' => function ($model) {
                        return $model->requestStore->name;
                    },
                ],
                [
                    'label' => 'Филиалы-источники',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $stores = $model->getSourceStores();
                        $names = array_map(function($store) {
                            return $store->name;
                        }, $stores);
                        return implode('<br>', $names);
                    },
                ],
                [
                    'attribute' => 'created_by_name',
                    'label' => 'Создал',
                    'value' => function ($model) {
                        return $model->createdBy->fullname;
                    },
                ],
                [
                    'attribute' => 'date_from',
                    'label' => 'Дата от',
                    'format' => ['date', 'php:d.m.Y'],
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date_from',
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd',
                        ],
                    ]),
                    'value' => function ($model) {
                        return Yii::$app->formatter->asDate($model->created_at, 'php:d.m.Y');
                    },
                    'headerOptions' => ['style' => 'width: 150px;'],
                ],
                [
                    'attribute' => 'date_to',
                    'label' => 'Дата до',
                    'format' => ['date', 'php:d.m.Y'],
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date_to',
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd',
                        ],
                    ]),
                    'value' => function ($model) {
                        return Yii::$app->formatter->asDate($model->created_at, 'php:d.m.Y');
                    },
                    'headerOptions' => ['style' => 'width: 150px;'],
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Статус',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $class = [
                            StoreTransfer::STATUS_NEW => 'label-primary',
                            StoreTransfer::STATUS_IN_PROGRESS => 'label-warning',
                            StoreTransfer::STATUS_COMPLETED => 'label-success',
                            StoreTransfer::STATUS_CANCELLED => 'label-default',
                        ];
                        return '<span class="label ' . ($class[$model->status] ?? 'label-default') . '">' . $model->getStatusLabel() . '</span>';
                    },
                    'filter' => [
                        StoreTransfer::STATUS_NEW => 'Новая',
                        StoreTransfer::STATUS_IN_PROGRESS => 'В работе',
                        StoreTransfer::STATUS_COMPLETED => 'Завершена',
                        StoreTransfer::STATUS_CANCELLED => 'Отменена',
                    ],
                    'headerOptions' => ['style' => 'width: 120px;'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {approve}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                                'title' => 'Просмотр',
                                'class' => 'btn btn-info btn-sm',
                            ]);
                        },
                        'approve' => function ($url, $model) {
                            if (in_array($model->status, [StoreTransfer::STATUS_NEW, StoreTransfer::STATUS_IN_PROGRESS])) {
                                return Html::a('<span class="glyphicon glyphicon-check"></span>', ['admin-approve', 'id' => $model->id], [
                                    'title' => 'Утвердить',
                                    'class' => 'btn btn-success btn-sm',
                                ]);
                            }
                            return '';
                        },
                    ],
                    'headerOptions' => ['style' => 'width: 100px;'],
                    'contentOptions' => ['class' => 'text-center'],
                ],
            ],
        ]); ?>
    </div>
</div>
