<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\StoreSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Склады';
$this->params['breadcrumbs'][] = $this->title;
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
            'tableOptions' => [
                'class' => 'table table-striped table-hover',
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
                [
                    'attribute' => 'name',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->name, ['stores/view', 'id' => $model->id]);
                    }
                ],
//            'type',
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
            ],
        ]); ?>
    </div>
</div>
