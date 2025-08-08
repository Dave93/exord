<?php

use app\models\Dashboard;
use app\models\Products;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $categories array */
/* @var $date string */
/* @var $store_id string */

$this->title = "Добавить группу продуктов";
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <div class="content table-responsive">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>
