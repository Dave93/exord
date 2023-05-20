<?php

use app\models\User;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\StoreSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Остатки за день';
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
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover table-striped',
            ],
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 40,
                    ]
                ],
                [
                    'label' => 'Дата синхронизации',
                    'attribute' => 'created_at',
                    'format' => 'html',
                    'value' => function($model) {
                        return  date('d.m.Y H:i:s', strtotime($model->created_at));
                    }
                ],
                [
                    'label' => 'Название',
                    'attribute' => 'store_name',
                    'format' => 'html',
                    'value' => function($model) {
                        return Html::a($model->store_name, ['daily-store-balance/view', 'id' => $model->id]);
                    }
                ],
            ],
        ]); ?>
    </div>
</div>