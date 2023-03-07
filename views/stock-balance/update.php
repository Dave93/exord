<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\StockBalance */

$this->title = 'Update Stock Balance: ' . $model->store;
$this->params['breadcrumbs'][] = ['label' => 'Stock Balances', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->store, 'url' => ['view', 'store' => $model->store, 'product' => $model->product]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="stock-balance-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
