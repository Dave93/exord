<?php

use app\models\Products;
use app\models\Zone;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = 'Продукты';
$this->params['breadcrumbs'][] = $this->title;

$model = new Products();
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => [
                'class' => 'table table-striped table-hover',
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 60,
                    ]
                ],
                [
                    'attribute' => 'name',
                    'label' => 'Наименование',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if (empty($model['productType']))
                            return Html::a($model['name'], ['products/subs', 'id' => $model['id']]);
                        return $model['name'];
                    }
                ],
//                'mainUnit',
//                'num',
                //'cookingPlaceType',
                //'productType',
                //'price_start',
                //'price_end',
                //'alternative_price',
                //'alternative_date',
                //'syncDate',
//                'delta',
                //'inStock',
                //'description:ntext',
//                'minBalance',
//                [
//                    'attribute' => 'zone',
//                    'filter' => Zone::getList(),
//                ],
            ],
        ]); ?>
    </div>
</div>