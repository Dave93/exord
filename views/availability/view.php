<?php

use app\models\Dashboard;
use app\models\User;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $searchModel app\models\OrderItemSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "Заказ #" . $model->id;
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
//            'filterModel' => $searchModel,
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
                        'attribute' => 'productId',
                        'value' => function ($model) {
                            return $model->product->name;
                        }
                    ],
                    [
                        'label' => 'Ед. Изм.',
                        'value' => function ($model) {
                            return $model->product->mainUnit;
                        },
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'quantity',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'storeQuantity',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
//                    [
//                        'attribute' => 'factStoreQuantity',
//                        'headerOptions' => [
//                            'class' => 'text-center'
//                        ],
//                        'contentOptions' => [
//                            'class' => 'text-center'
//                        ]
//                    ],
                    [
                        'attribute' => 'supplierQuantity',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
//                    [
//                        'attribute' => 'factSupplierQuantity',
//                        'headerOptions' => [
//                            'class' => 'text-center'
//                        ],
//                        'contentOptions' => [
//                            'class' => 'text-center'
//                        ]
//                    ],
//                'purchaseQuantity',
                ],
            ]); ?>
        </div>
        <hr>
        <h3 class="title">Комментарий</h3>
        <?= Yii::$app->formatter->asNtext($model->comment) ?>
        <hr>
        <?php if ($model->state == 2 && Yii::$app->user->identity->role == User::ROLE_ADMIN): ?>
            <div class="text-right mb20">
                <?= Html::a('Вернуть', ['orders/return', 'id' => $model->id], [
                    'class' => 'btn btn-warning btn-fill',
                    'data-confirm' => 'Вы действительно хотите вернуть заказ #' . $model->id . '?',
                    'data-pjax' => 0,
                    'data-method' => 'post'
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($model->state != 2 && Yii::$app->user->identity->role == User::ROLE_ADMIN): ?>
            <?php if ($model->canClose()): ?>
                <div class="text-right mb20">
                    <?= Html::a('Закрыть заказ', ['orders/close', 'id' => $model->id], [
                        'class' => 'btn btn-danger btn-fill',
                        'data-confirm' => 'Вы действительно хотите закрыть заказ #' . $model->id . '?',
                        'data-pjax' => 0,
                        'data-method' => 'post'
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="text-right mb20"><em>*Что бы закрыть заказ запольните все данные правильно!</em></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
