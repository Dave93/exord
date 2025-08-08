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
/* @var $model app\models\WriteOff */
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
            <div class="form-group field-writeoff-order_number">
                <label class="control-label">Филиал</label>
                <div class="form-control">
                    <?=$model->user->store->name?>
                </div>
                <div class="help-block"></div>
            </div>
            <div class="form-group field-writeoff-order_number">
                <label class="control-label">Пользователь</label>
                <div class="form-control">
                    <?=$model->user->fullname?>
                </div>
                <div class="help-block"></div>
            </div>
            <?= $form->field($model, 'order_number')->textInput() ?>
            <?= $form->field($model, 'customer_phone')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'write_price')->textInput() ?>

            <?= $form->field($model, 'blame')->dropDownList(\app\models\WriteOff::getBlameDropdown(), ['class' => 'selectpicker form-control show-tick', 'prompt' => 'Не выбран виновник', 'data-header' => "Выберите виновника", 'data-live-search' => 'true']) ?>
            <?= $form->field($model, 'comment')->textarea() ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
