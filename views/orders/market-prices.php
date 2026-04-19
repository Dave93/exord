<?php

use app\models\Orders;
use app\models\OrderItems;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Заполнение цен базара';
$this->params['breadcrumbs'][] = $this->title;

$bazarCount = function ($orderId) {
    return (int)(new \yii\db\Query())
        ->from('order_items oi')
        ->innerJoin('product_groups_link pgl', 'pgl.productId = oi.productId')
        ->innerJoin('product_groups pg', 'pg.id = pgl.productGroupId')
        ->where([
            'oi.orderId' => $orderId,
            'pg.is_market' => 1,
            'oi.deleted_at' => null,
        ])
        ->count();
};

$bazarFilledCount = function ($orderId) {
    return (int)(new \yii\db\Query())
        ->from('order_items oi')
        ->innerJoin('product_groups_link pgl', 'pgl.productId = oi.productId')
        ->innerJoin('product_groups pg', 'pg.id = pgl.productGroupId')
        ->where([
            'oi.orderId' => $orderId,
            'pg.is_market' => 1,
            'oi.deleted_at' => null,
        ])
        ->andWhere(['is not', 'oi.market_total_price', null])
        ->andWhere(['>', 'oi.market_total_price', 0])
        ->count();
};
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => false,
            'tableOptions' => ['class' => 'table table-hover'],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn', 'contentOptions' => ['width' => 40, 'class' => 'text-center']],
                [
                    'attribute' => 'id',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->id, ['orders/market-prices-fill', 'id' => $model->id]);
                    },
                ],
                [
                    'label' => 'Филиал',
                    'value' => function ($model) {
                        return $model->store ? $model->store->name : '-';
                    },
                ],
                [
                    'attribute' => 'date',
                    'value' => function ($model) {
                        return date('d.m.Y', strtotime($model->date));
                    },
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date',
                        'value' => $searchModel->date,
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => ['format' => 'yyyy-mm-dd', 'todayHighlight' => true],
                    ]),
                    'contentOptions' => ['width' => 120, 'class' => 'text-center'],
                ],
                [
                    'label' => 'Базарных позиций',
                    'value' => function ($model) use ($bazarCount, $bazarFilledCount) {
                        $total = $bazarCount($model->id);
                        $filled = $bazarFilledCount($model->id);
                        return "{$filled} / {$total}";
                    },
                    'contentOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'state',
                    'value' => function ($model) {
                        return Orders::$states[$model->state];
                    },
                    'filter' => false,
                    'contentOptions' => ['width' => 180, 'class' => 'text-center'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{fill}',
                    'buttons' => [
                        'fill' => function ($url, $model) {
                            return Html::a('Заполнить', ['orders/market-prices-fill', 'id' => $model->id], [
                                'class' => 'btn btn-success btn-xs',
                            ]);
                        },
                    ],
                    'contentOptions' => ['width' => 120, 'class' => 'text-center'],
                ],
            ],
        ]); ?>
    </div>
</div>
