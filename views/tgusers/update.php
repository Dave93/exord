<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = 'Изменить';
$this->params['breadcrumbs'][] = ['label' => 'Telegram Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изменить';
?>
<div class="card">
    <div class="header">
        <h2 class="title"><?= Html::encode($this->title) ?></h2>
    </div>
    <div class="content table-responsive">
        <?= $this->render('_form', [
            'model' => $model,
            'checked' => $checked,
        ]) ?>
    </div>
</div>

