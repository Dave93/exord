<?php

use app\models\Stores;
use app\models\Suppliers;
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Пользователи';
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
                    'attribute' => 'fullname',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->fullname, ['user/view', 'id' => $model->id]);
                    }
                ],
                'username',
                [
                    'attribute' => 'role',
                    'filter' => User::$roles,
                    'value' => function ($model) {
                        return User::$roles[$model->role];
                    }
                ],
                [
                    'attribute' => 'store_id',
                    'filter' => Stores::getList(),
                    'value' => function ($model) {
                        if (empty($model->store_id))
                            return "-";
                        return $model->store->name;
                    }
                ],
                [
                    'attribute' => 'supplier_id',
                    'filter' => Suppliers::getList(),
                    'value' => function ($model) {
                        if (empty($model->supplier_id))
                            return "-";
                        return $model->supplier->name;
                    }
                ],
                [
                    'label' => 'Категории',
                    'value' => function ($model) {
                        $list = count($model->categories);

//                        foreach ($model->categories as $category) {
//                            $list .= $category->name . "; ";
//                        }
                        $text = empty($list) ? 'Все' : $list . ' тип продуктов';
                        return $text;
                    },
                    'contentOptions' => [
                        'class' => 'text-nowrap'
                    ]
                ],
                //'password',
                [
                    'attribute' => 'percentage',
                    'value' => function ($model) {
                        return $model->percentage . ' %';
                    },
                    'contentOptions' => [
                        'class' => 'text-center'
                    ]
                ],
                //'email:email',
                //'description:ntext',
                //'authKey',
                //'accessToken',
                //'regDate',
                [
                    'attribute' => 'state',
                    'filter' => User::$states,
                    'value' => function ($model) {
                        return User::$states[$model->state];
                    }
                ],
                [
                    'attribute' => 'lastVisit',
                    'value' => function ($model) {
                        if (empty($model->lastVisit))
                            return "-";
                        return date("d.m.y H:i", strtotime($model->lastVisit));
                    },
                    'contentOptions' => [
                        'width' => 140,
                        'class' => 'text-center'
                    ]
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
