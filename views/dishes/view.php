<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Dishes */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Блюда', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', ['index'], ['class' => 'btn btn-primary btn-fill']) ?>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-warning btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content">
        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id',
                'name',
                'unit',
                [
                    'attribute' => 'active',
                    'value' => $model->active ? 'Да' : 'Нет',
                ],
            ],
        ]) ?>
    </div>
</div>
