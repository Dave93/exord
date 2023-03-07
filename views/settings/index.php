<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Настройки';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('<span class="glyphicon glyphicon-refresh"></span> Синхронизировать с iiko', "#", ['class' => 'btn btn-primary btn-fill', 'data-action' => 'sync-iiko']) ?>
            <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success btn-fill']) ?>
        </p>
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
                        'class' => 'text-center'
                    ]
                ],
                'title',
                'key',
                'value',
                [
                    'attribute' => 'created',
                    'value' => function ($model) {
                        return date("d.m.Y H:i", strtotime($model->created));
                    }
                ],
                [
                    'attribute' => 'author_id',
                    'value' => function ($model) {
                        return $model->author->fullname;
                    },
                    'contentOptions' => [
                        'width' => 200,
                    ]
                ],

                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update}',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
