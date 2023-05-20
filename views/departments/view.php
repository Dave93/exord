<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Departments */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Филиалы', 'url' => ['index']];
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
//            'departmentId',
                'name',
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

//            ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    </div>
</div>