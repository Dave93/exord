<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProductWriteoff */

$this->title = 'Редактирование списания #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Списания', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Списание #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>

<div class="card">
    <div class="header">
        <h4 class="title"><?= Html::encode($this->title) ?></h4>
    </div>
    <div class="content">
        <?php $form = ActiveForm::begin(['options' => ['class' => 'form-horizontal']]); ?>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="col-md-3 control-label">Магазин</label>
                    <div class="col-md-9">
                        <p class="form-control-static"><?= Html::encode($model->store->name) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="col-md-3 control-label">Продукт</label>
                    <div class="col-md-9">
                        <p class="form-control-static"><?= Html::encode($model->product->name) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'count', [
                    'template' => '<label class="col-md-3 control-label">{label}</label><div class="col-md-9">{input}{error}</div>',
                    'options' => ['class' => 'form-group'],
                ])->textInput([
                    'type' => 'number',
                    'step' => 'any',
                    'min' => 0,
                ])->label('Количество (' . $model->product->mainUnit . ')') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-9">
                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
                        <?= Html::a('Отмена', ['view', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
