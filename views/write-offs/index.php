<?php

use app\models\Stores;
use app\models\Suppliers;
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Списания';
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" />

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
                'date',
                'customer_phone',
                [
                    'attribute' => 'tg_user_id',
                    'filter' => \app\models\TgUsers::getUsers(),
                    'value' => function ($model) {
                        return \app\models\TgUsers::getUsers()[$model->tg_user_id];
                    }
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
                    'template' => '{update} {delete} {play}',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ],
                    'buttons' => [
                        'play' => function ($url, $model, $key) {
                            return '<div><span data-fancybox data-src="#'.$model->id.'" href="javascript:;" class="glyphicon glyphicon-play"></span><div style="display: none;" id="'.$model->id.'"><video controls><source src="'.$model->video.'" type="video/mp4"></video></div></div>';
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('[data-fancybox]').fancybox({
            afterShow: function(instance, slide) {
                slide.$slide.find('video').get(0).play();
            },
            afterClose: function(instance, slide) {
                // slide.$slide.find('video').get(0).pause();
            }
        });
    });
</script>