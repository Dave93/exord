<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Zone */

$this->title = 'Добавить';
$this->params['breadcrumbs'][] = ['label' => 'Зона', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header">
        <h2 class="title"><?= Html::encode($this->title) ?></h2>
    </div>
    <div class="content table-responsive">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
