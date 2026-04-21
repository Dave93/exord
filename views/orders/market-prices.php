<?php

use app\models\Orders;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $start string */
/* @var $end string */

$this->title = 'Цены базара';
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
    <div class="content">
        <form method="get" class="form-inline" style="margin-bottom: 15px;">
            <div class="form-group">
                <label>С:&nbsp;</label>
                <?= DatePicker::widget([
                    'name' => 'start',
                    'value' => $start,
                    'type' => DatePicker::TYPE_INPUT,
                    'pluginOptions' => ['format' => 'yyyy-mm-dd', 'todayHighlight' => true, 'autoclose' => true],
                ]) ?>
            </div>
            &nbsp;
            <div class="form-group">
                <label>По:&nbsp;</label>
                <?= DatePicker::widget([
                    'name' => 'end',
                    'value' => $end,
                    'type' => DatePicker::TYPE_INPUT,
                    'pluginOptions' => ['format' => 'yyyy-mm-dd', 'todayHighlight' => true, 'autoclose' => true],
                ]) ?>
            </div>
            &nbsp;
            <button type="submit" class="btn btn-primary btn-fill">Применить</button>
        </form>

        <div class="table-responsive">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'summary' => false,
                'tableOptions' => ['class' => 'table table-hover'],
                'rowOptions' => function ($model) use ($bazarCount, $bazarFilledCount) {
                    $total = $bazarCount($model->id);
                    $filled = $bazarFilledCount($model->id);
                    if ($total === 0 || $filled === 0) {
                        return [];
                    }
                    if ($filled < $total) {
                        return ['class' => 'bg-orange'];
                    }
                    return ['class' => 'bg-green'];
                },
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn', 'contentOptions' => ['width' => 40, 'class' => 'text-center']],
                    [
                        'attribute' => 'id',
                        'label' => '№',
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
                        'label' => 'Дата',
                        'value' => function ($model) {
                            return date('d.m.Y', strtotime($model->date));
                        },
                        'contentOptions' => ['width' => 120, 'class' => 'text-center'],
                    ],
                    [
                        'label' => 'Базарные позиции (заполнено / всего)',
                        'value' => function ($model) use ($bazarCount, $bazarFilledCount) {
                            $total = $bazarCount($model->id);
                            $filled = $bazarFilledCount($model->id);
                            return "{$filled} / {$total}";
                        },
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    [
                        'attribute' => 'state',
                        'label' => 'Статус',
                        'value' => function ($model) {
                            return Orders::$states[$model->state] ?? $model->state;
                        },
                        'contentOptions' => ['width' => 140, 'class' => 'text-center'],
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
</div>
