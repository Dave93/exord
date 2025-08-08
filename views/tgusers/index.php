<?php

use app\models\Stores;
use app\models\Suppliers;
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Telegram Пользователи';
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
            'tableOptions' => [
                'class' => 'table table-striped table-hover top-table',
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 40,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'active',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ],
                    'filter' => [
                        0 => 'Не активен',
                        1 => 'Активен'
                    ],
                    'format' => 'html',
                    'value' => function ($model) {
                        return $model->active ? '<span class="label label-success">Активен</span>' : '<span class="label label-danger">Не активен</span>';
                    },
                ],
                [
                    'attribute' => 'name',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->name, ['tgusers/view', 'id' => $model->id]);
                    }
                ],
                [
                    'attribute' => 'phone',
                    'contentOptions' => [
                        'width' => 150,
                    ],
                ],
                [
                    'attribute' => 'user_id',
                    'filter' => User::getUsers(),
                    'value' => function ($model) {
                        return User::getUsers()[$model->user_id];
                    }
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update} {delete}',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
