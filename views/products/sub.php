<?php

use app\models\Products;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Продукты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="products-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => [
            'class' => 'table table-hover',
        ],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => [
                    'width' => 40,
                ]
            ],

//            'id',
//            'parentId',
//            'code',
//            'num',
            'name',
            'mainUnit',
            'cookingPlaceType',
            [
                'attribute' => 'productType',
                'filter' => Products::$types,
                'value' => function ($model) {
                    return Products::$types[$model->productType];
                }
            ],
            //'price_start',
            //'price_end',
            [
                'attribute' => 'syncDate',
                'value' => function ($model) {
                    return date("d.m.Y H:i", strtotime($model->syncDate));
                },
                'contentOptions' => [
                    'width' => 140,
                    'class' => 'text-right'
                ]
            ],
            //'delta',
            //'inStock',

//            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
