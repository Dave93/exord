<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Зона';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right"><?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success btn-fill']) ?></p>
    </div>
    <hr>
    <div class="content table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
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
//                'id',
                'name',
                'date',
                'author',
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update} {delete}',
                    'contentOptions' => [
                        'width' => 80,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
