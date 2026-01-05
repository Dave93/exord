<?php

use app\models\StoreTransfer;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\StoreTransferSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Внутренние перемещения';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card">
    <div class="header">
        <h4 class="title">
            <?= Html::encode($this->title) ?>
            <div class="pull-right">
                <?= Html::a('<i class="glyphicon glyphicon-download-alt"></i> Excel', ['export-excel'] + Yii::$app->request->queryParams, ['class' => 'btn btn-default btn-fill btn-sm', 'style' => 'margin-right: 5px;']) ?>
                <?= Html::a('Входящие заявки', ['incoming'], ['class' => 'btn btn-info btn-fill btn-sm']) ?>
                <?= Html::a('Создать заявку', ['create'], ['class' => 'btn btn-success btn-fill btn-sm']) ?>
            </div>
        </h4>
        <p class="category">Заявки на перемещение продуктов между филиалами</p>
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
                        return implode(', ', $names);
                    },
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'Дата создания',
                    'format' => ['date', 'php:d.m.Y H:i'],
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
                    'template' => '{view} {update} {cancel}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                                'title' => 'Просмотр',
                                'class' => 'btn btn-info btn-sm',
                            ]);
                        },
                        'update' => function ($url, $model) {
                            if ($model->canEdit()) {
                                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                                    'title' => 'Редактировать',
                                    'class' => 'btn btn-primary btn-sm',
                                ]);
                            }
                            return '';
                        },
                        'cancel' => function ($url, $model) {
                            if ($model->canCancel()) {
                                return Html::a('<span class="glyphicon glyphicon-remove"></span>', $url, [
                                    'title' => 'Отменить',
                                    'class' => 'btn btn-danger btn-sm',
                                    'data' => [
                                        'confirm' => 'Вы уверены, что хотите отменить эту заявку?',
                                        'method' => 'post',
                                    ],
                                ]);
                            }
                            return '';
                        },
                    ],
                    'headerOptions' => ['style' => 'width: 150px;'],
                    'contentOptions' => ['class' => 'text-center'],
                ],
            ],
        ]); ?>
    </div>
</div>
