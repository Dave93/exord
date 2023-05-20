<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProductSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="products-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'parentId') ?>

    <?= $form->field($model, 'code') ?>

    <?= $form->field($model, 'num') ?>

    <?= $form->field($model, 'name') ?>

    <?php // echo $form->field($model, 'mainUnit') ?>

    <?php // echo $form->field($model, 'cookingPlaceType') ?>

    <?php // echo $form->field($model, 'productType') ?>

    <?php // echo $form->field($model, 'price_start') ?>

    <?php // echo $form->field($model, 'price_end') ?>

    <?php // echo $form->field($model, 'syncDate') ?>

    <?php // echo $form->field($model, 'delta') ?>

    <?php // echo $form->field($model, 'inStock') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
