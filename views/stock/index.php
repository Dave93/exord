<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Приходы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="stock-index">

    <div class="page-header clearfix">
        <div class="pull-left">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="pull-right">
            <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => [
                    'width' => 60,
                ]
            ],

//            'product_id',
            [
                'label' => 'Дата',
                'value' => function ($model) {
                    return $model['date'];
                }
            ],
            [
                'label' => 'Автор',
//                'value' => function ($model) {
//                    return \app\models\User::getFullName($model['author_id']);
//                }
            ],

//            'amount',
//            'add_date',

            [
                'class' => 'yii\grid\ActionColumn'
            ],
        ],
    ]); ?>
</div>
