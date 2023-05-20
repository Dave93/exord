<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\StockBalance */

$this->title = 'Create Stock Balance';
$this->params['breadcrumbs'][] = ['label' => 'Stock Balances', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="stock-balance-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
