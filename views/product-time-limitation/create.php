<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ProductTimeLimitation */
/* @var $products array */

$this->title = 'Создать ограничение по времени';
$this->params['breadcrumbs'][] = ['label' => 'Ограничения по времени для продуктов', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content">
        <div class="product-time-limitation-create">
            <?= $this->render('_form', [
                'model' => $model,
                'products' => $products,
            ]) ?>
        </div>
    </div>
</div> 