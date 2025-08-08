<?php

use app\models\Dashboard;
use app\models\Orders;
use app\models\Stores;
use app\models\User;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Группы продуктов';
$this->params['breadcrumbs'][] = $this->title;
$isOrderMan = Dashboard::isOrderMan();
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <div class="pull-right">
            <?= Html::a('Добавить', ['add'], ['class' => 'btn btn-success btn-fill']) ?>
        </div>
    </div>
    <hr>
    <div class="content">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_BUYER, User::ROLE_STOCK, User::ROLE_MANAGER])) ? $searchModel : false,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover',
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
                    'attribute' => 'id'
                ],
                [
                    'attribute' => 'name'
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update}',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>
