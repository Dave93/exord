<?php

use app\models\Stores;
use app\models\Suppliers;
use app\models\UserCategories;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\User;
use app\models\Products;
use app\models\Groups;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="user-form">

    <!--  show yii flash with key error if it exists  -->
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif ?>
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'active')->checkbox() ?>
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'user_id')->dropDownList(User::getList(), ['class' => 'selectpicker form-control show-tick', 'prompt' => 'Все филиалы', 'data-header' => "Выберите филиал", 'data-live-search' => 'true']) ?>
        </div>

    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
