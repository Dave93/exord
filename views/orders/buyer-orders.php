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
            <?php if ($isOrderMan): ?>
                <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
            <?php elseif (Yii::$app->user->identity->role == User::ROLE_STOCK): ?>
                <?= Html::a('Добавить', ['stock-order'], ['class' => 'btn btn-success btn-fill']) ?>
            <?php endif; ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_BUYER, User::ROLE_STOCK, User::ROLE_MANAGER])) ? $searchModel : false,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-striped table-hover',
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
                        return $model->id;
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
                    'value' => function ($model) {
                        return $model->store->name;
                    },
                    'visible' => !$isOrderMan
                ],
                [
                    'attribute' => 'userId',
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
//                'add_date',
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
