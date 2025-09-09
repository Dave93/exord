<?php

use app\models\Dashboard;
use app\models\Orders;
use app\models\Stores;
use app\models\User;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

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
            <?php if ($isOrderMan): ?>
                <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
            <?php elseif (Yii::$app->user->identity->role == User::ROLE_STOCK): ?>
                <?= Html::a('Добавить', ['stock-order'], ['class' => 'btn btn-success btn-fill']) ?>
            <?php endif; ?>
        </p>
    </div>
    <hr>
    <div class="content">
        <?php
        $form = ActiveForm::begin([
            'id' => 'filterForm',
            'method' => 'get',
            'action' => ['/orders']
        ]);
        ?>
            <div class="row">
                    <div class="input-daterange datepicker align-items-center" data-date-format="dd.mm.yyyy"
                         data-today-highlight="1">
                        <div class="col-xs-2">
                            <div class="form-group">
                                    <input name="start" class="form-control" placeholder="Дата с" type="text"
                                           autocomplete="off"
                                           value="<?= $start ?>" />
                            </div>
                        </div>
                        <div class="col-xs-2">
                            <div class="form-group">
                                    <input name="end" class="form-control" placeholder="по" type="text"
                                           autocomplete="off"
                                           value="<?= $end ?>" />
                            </div>
                        </div>
                </div>
                <div class="col-xs-2">
                    <?= Html::submitButton('Показать', ['class' => 'btn btn-success']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_BUYER, User::ROLE_STOCK, User::ROLE_MANAGER])) ? $searchModel : false,
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
                    'value' => function ($model) use ($isOrderMan) {
                        if ($isOrderMan)
                            return Html::a($model->id, ['orders/update', 'id' => $model->id]);
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
                    'label' => 'Приход',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a("Со склада", ['orders/fact-stock', 'id' => $model->id]);
                    },
                    'visible' => $isOrderMan && Yii::$app->user->identity->role != User::ROLE_STOCK
                ],
                [
                    'label' => 'Приход',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a("От поставщика", ['orders/fact-supplier', 'id' => $model->id]);
                    },
                    'visible' => $isOrderMan && Yii::$app->user->identity->role != User::ROLE_STOCK
                ],
                [
                    'label' => 'Приход',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a("От поставщика", ['orders/stock-fact-supplier', 'id' => $model->id]);
                    },
                    'visible' => Yii::$app->user->identity->role == User::ROLE_STOCK
                ],
                [
                    'attribute' => 'storeId',
                    'filter' => Html::activeDropDownList($searchModel, 'storeId', Stores::getList(), ['class' => 'selectpicker form-control show-tick', 'prompt' => 'Все', 'data-header' => "Выберите склад", 'data-live-search' => 'true']),
                    'value' => function ($model) {
                        return $model->store->name;
                    },
                    'visible' => !$isOrderMan
                ],
                [
                    'attribute' => 'userId',
                    'filter' => Html::activeDropDownList($searchModel, 'userId', User::getList(), ['class' => 'selectpicker form-control show-tick', 'prompt' => 'Все', 'data-header' => "Выберите заказчика", 'data-live-search' => 'true']),
                    'value' => function ($model) {
                        return $model->user->fullname;
                    },
                    'visible' => !$isOrderMan
                ],
                [
                    'attribute' => 'date',
                    'value' => function ($model) {
                        return date("d.m.Y", strtotime($model->date));
                    },
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date',
                        'value' => $searchModel->date,
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true
                        ]
                    ]),
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
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'addDate',
                        'value' => $searchModel->date,
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true
                        ]
                    ]),
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center text-nowrap'
                    ]
                ],
                [
                    'label' => 'Дата удаления',
                    'value' => function ($model) {
                        if ($model->deleted_at != null) {
                            return date("d.m.Y H:i", strtotime($model->deleted_at));
                        }
                        return null;
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center text-nowrap'
                    ],
                    'visible' => Yii::$app->user->identity->role == User::ROLE_ADMIN
                ],
                [
                    'label' => 'Кто удалил',
                    'value' => function ($model) {
                        if ($model->deleted_by != null && $model->deletedBy) {
                            return $model->deletedBy->fullname;
                        }
                        return null;
                    },
                    'visible' => Yii::$app->user->identity->role == User::ROLE_ADMIN
                ],
                [
                    'attribute' => 'state',
                    'filter' => Orders::$states,
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
                    'template' => '{view} {try_again} {return} {return_to_new}',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ],
                    'buttons' => [
                        'view'=>function ($url, $model) {
                            $customurl=Yii::$app->getUrlManager()->createUrl(['orders/view','id'=>$model['id']]);
                            return \yii\helpers\Html::a( '<span class="glyphicon glyphicon-eye-open"></span>', $customurl,
                                ['title' => Yii::t('yii', 'View'), 'data-pjax' => '0']);
                        },
                        'try_again' => function ($url, $model) {
                            $customurl=Yii::$app->getUrlManager()->createUrl(['orders/try-again','id'=>$model['id'], 'back' => urlencode(Yii::$app->request->url)]);
                            return \yii\helpers\Html::a( '<span class="glyphicon glyphicon-refresh"></span>', $customurl,
                                ['title' => Yii::t('yii', 'Подтвердить'), 'data-pjax' => '0']);
                        },
                        'return' => function ($url, $model) {
                            if (Yii::$app->user->identity->role == User::ROLE_ADMIN && $model->deleted_at != null) {
                                $customurl=Yii::$app->getUrlManager()->createUrl(['orders/return-back','id'=>$model['id'], 'back' => urlencode(Yii::$app->request->url)]);
                                return \yii\helpers\Html::a( '<span class="glyphicon glyphicon-share-alt"></span>', $customurl,
                                    ['title' => Yii::t('yii', 'Подтвердить'), 'data-pjax' => '0']);
                            }
                        },
                        'return_to_new' => function ($url, $model) {
                            if (Yii::$app->user->identity->role == User::ROLE_ADMIN && $model->state == 1) {
                                $customurl=Yii::$app->getUrlManager()->createUrl(['orders/return-to-new','id'=>$model['id'], 'back' => urlencode(Yii::$app->request->url)]);
                                return \yii\helpers\Html::a( '<span class="glyphicon glyphicon-backward"></span>', $customurl,
                                    ['title' => Yii::t('yii', 'Вернуть в Новый'), 'data-pjax' => '0']);
                            }
                        }
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
