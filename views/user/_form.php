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

if (!$model->isNewRecord)
    $selected = Yii::$app->db->createCommand("select category_id from user_categories where user_id=:u")
        ->bindValue(':u', $model->id, PDO::PARAM_INT)
        ->queryColumn();
else
    $selected = [];
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'fullname')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

            <?php if ($model->isNewRecord): ?>
                <?= $form->field($model, 'password')->passwordInput(['maxlength' => true, 'value' => ""]) ?>
            <?php else: ?>
                <?= $form->field($model, 'newPassword')->passwordInput(['maxlength' => true, 'value' => ""]) ?>
            <?php endif ?>

            <div class="row">
                <div class="col-md-8">
                    <?= $form->field($model, 'percentage')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-4 text-right" style="padding-top: 40px;">
                    <?= $form->field($model, 'showPrice')->checkbox() ?>
                </div>
            </div>

            <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'oil_tg_id')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'state')->dropDownList(User::$states, ['class' => 'selectpicker form-control show-tick']) ?>

            <?= $form->field($model, 'role')->dropDownList(User::$roles, ['class' => 'selectpicker form-control  show-tick', 'data-header' => "Выберите роль",]) ?>

            <?= $form->field($model, 'store_id')->dropDownList(Stores::getList(), ['class' => 'selectpicker form-control show-tick', 'prompt' => 'Все филиалы', 'data-header' => "Выберите филиал", 'data-live-search' => 'true']) ?>

            <?= $form->field($model, 'supplier_id')->dropDownList(Suppliers::getList(), ['class' => 'selectpicker form-control  show-tick', 'prompt' => 'Выберите поставщика', 'data-header' => "Выберите поставщика", 'data-live-search' => 'true']) ?>

            <?= $form->field($model, 'terminalId')->dropDownList(\app\models\Terminals::getList(), ['class' => 'selectpicker form-control  show-tick', 'prompt' => 'Выберите филиал', 'data-header' => "Выберите филиал", 'data-live-search' => 'true']) ?>

            <?= $form->field($model, 'product_group_id')->dropDownList(\app\models\ProductGroups::getList(), ['class' => 'selectpicker form-control  show-tick', 'prompt' => 'Выберите этаж', 'data-header' => "Выберите этаж", 'data-live-search' => 'true']) ?>

            <div class="form-group categories required">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="title" style="font-size: 20px;">Категория</h4>
                    </div>
                    <div class="col-md-6 text-right" style="line-height: 40px;">
                        <a href="#" data-action="selectAll">Выбрать все</a> | <a href="#" data-action="deselectAll">Убрать
                            все</a>
                    </div>
                </div>
                <div class="product-tree">
                    <?= Products::getHierarchy(0, $selected) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
