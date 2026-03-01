<?php

use app\models\MealOrders;
use app\models\User;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\MealOrders */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $showDeleted int */

$this->title = "Заказ блюд #" . $model->id;
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

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <?php if (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])): ?>
        <div class="content" style="padding-bottom: 10px;">
            <div class="form-group">
                <label class="checkbox-inline">
                    <input type="checkbox" id="show-deleted-toggle" <?= $showDeleted ? 'checked' : '' ?>>
                    Показать удалённые позиции
                </label>
            </div>
        </div>
        <hr>
    <?php endif; ?>

    <style>
        .deleted-row {
            background-color: #ffe6e6 !important;
            opacity: 0.7;
        }
        .deleted-row td {
            text-decoration: line-through;
            color: #999;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var checkbox = document.getElementById('show-deleted-toggle');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    var currentUrl = new URL(window.location.href);
                    if (this.checked) {
                        currentUrl.searchParams.set('showDeleted', '1');
                    } else {
                        currentUrl.searchParams.delete('showDeleted');
                    }
                    window.location.href = currentUrl.toString();
                });
            }
        });
    </script>

    <div class="content">
        <div class="table-responsive mb20">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'summary' => false,
                'tableOptions' => [
                    'class' => 'table table-hover table-striped',
                ],
                'rowOptions' => function ($model) {
                    if ($model->deleted_at !== null) {
                        return ['class' => 'deleted-row'];
                    }
                    return [];
                },
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'contentOptions' => [
                            'width' => 40,
                        ]
                    ],
                    [
                        'attribute' => 'dishId',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $dishName = $model->dish ? $model->dish->name : '-';
                            if ($model->deleted_at !== null) {
                                return $dishName . ' <span class="badge badge-danger" style="background-color: #dc3545; color: white; margin-left: 5px;">Удалено</span>';
                            }
                            return $dishName;
                        }
                    ],
                    [
                        'label' => 'Ед. Изм.',
                        'value' => function ($model) {
                            return $model->dish ? $model->dish->unit : '-';
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
                        'label' => 'Удалил',
                        'format' => 'raw',
                        'visible' => in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE]) && $showDeleted,
                        'value' => function ($model) {
                            if ($model->deleted_at !== null && $model->deletedBy !== null) {
                                return $model->deletedBy->username;
                            }
                            return '-';
                        },
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center']
                    ],
                    [
                        'label' => 'Дата удаления',
                        'format' => 'raw',
                        'visible' => in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE]) && $showDeleted,
                        'value' => function ($model) {
                            if ($model->deleted_at !== null) {
                                return Yii::$app->formatter->asDatetime($model->deleted_at, 'php:d.m.Y H:i');
                            }
                            return '-';
                        },
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center']
                    ],
                    [
                        'label' => 'Действия',
                        'format' => 'raw',
                        'visible' => in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE]) && $showDeleted,
                        'value' => function ($model) {
                            if ($model->deleted_at !== null && in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])) {
                                return Html::a(
                                    '<i class="fa fa-undo"></i> Восстановить',
                                    ['restore-item', 'mealOrderId' => $model->mealOrderId, 'dishId' => $model->dishId],
                                    [
                                        'class' => 'btn btn-success btn-sm',
                                        'data-confirm' => 'Вы действительно хотите восстановить эту позицию?',
                                        'data-method' => 'post',
                                    ]
                                );
                            }
                            return '';
                        },
                        'headerOptions' => ['class' => 'text-center', 'style' => 'width: 150px;'],
                        'contentOptions' => ['class' => 'text-center']
                    ],
                ],
            ]); ?>
        </div>

        <hr>
        <h3 class="title">Комментарий</h3>
        <?= Yii::$app->formatter->asNtext($model->comment) ?>
        <hr>

        <?php if ($model->state == 0 && !$model->is_locked && in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_STOCK])): ?>
            <div class="text-right mb20">
                <?= Html::a('Отправить', ['meal-orders/send', 'id' => $model->id], [
                    'class' => 'btn btn-primary btn-fill',
                    'data-confirm' => 'Вы действительно хотите отправить заказ блюд #' . $model->id . '?',
                ]) ?>
            </div>
        <?php endif; ?>

        <?php if ($model->state != 2 && in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_STOCK])): ?>
            <?php if ($model->canClose()): ?>
                <div class="text-right mb20">
                    <?= Html::a('Закрыть заказ', ['meal-orders/close', 'id' => $model->id], [
                        'class' => 'btn btn-danger btn-fill',
                        'data-confirm' => 'Вы действительно хотите закрыть заказ блюд #' . $model->id . '?',
                        'data-pjax' => 0,
                        'data-method' => 'post'
                    ]) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($model->state == 2 && Yii::$app->user->identity->role == User::ROLE_ADMIN): ?>
            <div class="text-right mb20">
                <?= Html::a('Вернуть в Новый', ['meal-orders/return-to-new', 'id' => $model->id], [
                    'class' => 'btn btn-warning btn-fill',
                    'data-confirm' => 'Вернуть заказ блюд #' . $model->id . ' в статус Новый?',
                ]) ?>
            </div>
        <?php endif; ?>

        <?php if ($model->deleted_at == null && $model->state != 2 && Yii::$app->user->identity->role == User::ROLE_ADMIN): ?>
            <div class="text-right mb20">
                <?= Html::a('Удалить', ['meal-orders/delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger btn-fill',
                    'data-confirm' => 'Вы действительно хотите удалить заказ блюд #' . $model->id . '?',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
