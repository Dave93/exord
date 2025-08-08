<?php

use app\models\Dashboard;
use app\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = $model->fullname;
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="orders-list">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
//            'id',
                    'username',
                    'phone',
                    'email:email',
                    [
                        'attribute' => 'role',
                        'value' => User::$roles[$model->role]
                    ],
                    [
                        'attribute' => 'store_id',
                        'value' => $model->store->name
                    ],
                    [
                        'attribute' => 'supplier_id',
                        'value' => $model->supplier->name
                    ],
                    'description:ntext',
                    [
                        'attribute' => 'state',
                        'value' => User::$states[$model->state]
                    ],
                    [
                        'attribute' => 'regDate',
                        'value' => Dashboard::dateTime($model->regDate)
                    ],
                    [
                        'attribute' => 'lastVisit',
                        'value' => Dashboard::dateTime($model->lastVisit)
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
