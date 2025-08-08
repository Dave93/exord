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
    $selected = Yii::$app->db->createCommand("select productId from product_groups_link where productGroupId=:u")
        ->bindValue(':u', $model->id, PDO::PARAM_INT)
        ->queryColumn();
else
    $selected = [];
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="items-center justify-content-between">
        <div>
            <?= $form->field($model, 'name')->textInput() ?>
        </div>
        <div>
            <?= $form->field($model, 'is_market')->checkbox() ?>
        </div>
        <div>
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
                    <?= Products::getProductsGroupHierarchy(0, $selected) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<style>
    .items-center {
        display: grid;
        grid-template-columns: 2fr 2fr;
        align-items: center;
        gap: 10px;
    }
</style>
