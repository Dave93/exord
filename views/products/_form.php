<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Products */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="products-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'parentId')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'code')->textInput() ?>

    <?= $form->field($model, 'num')->textInput() ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'mainUnit')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cookingPlaceType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'productType')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'price_start')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'price_end')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'syncDate')->textInput() ?>

    <?= $form->field($model, 'delta')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'inStock')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
