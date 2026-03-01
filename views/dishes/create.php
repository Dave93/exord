<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Dishes */

$this->title = 'Добавить блюдо';
$this->params['breadcrumbs'][] = ['label' => 'Блюда', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', ['index'], ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
