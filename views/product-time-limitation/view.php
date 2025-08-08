<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\ProductTimeLimitation */

$this->title = $model->product->name;
$this->params['breadcrumbs'][] = ['label' => 'Ограничения по времени для продуктов', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <div class="pull-right">
            <?= Html::a('Редактировать', ['update', 'productId' => $model->productId], ['class' => 'btn btn-primary btn-fill']) ?>
            <?= Html::a('Удалить', ['delete', 'productId' => $model->productId], [
                'class' => 'btn btn-danger btn-fill',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить этот элемент?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
    </div>
    <hr>
    <div class="content">
        <div class="product-time-limitation-view">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'productId',
                        'value' => $model->product->name,
                        'label' => 'Продукт'
                    ],
                    [
                        'attribute' => 'startTime',
                        'format' => ['time', 'php:H:i'],
                        'label' => 'Время начала'
                    ],
                    [
                        'attribute' => 'endTime',
                        'format' => ['time', 'php:H:i'],
                        'label' => 'Время окончания'
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div> 