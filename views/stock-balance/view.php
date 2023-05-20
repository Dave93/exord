<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\StockBalance */

$this->title = $model->store;
$this->params['breadcrumbs'][] = ['label' => 'Stock Balances', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="stock-balance-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'store' => $model->store, 'product' => $model->product], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'store' => $model->store, 'product' => $model->product], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'store',
            'product',
            'amount',
            'sum',
        ],
    ]) ?>

</div>
