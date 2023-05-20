<?php

use app\models\Products;
use app\models\Zone;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = 'Продукты';
$this->params['breadcrumbs'][] = $this->title;

$model = new Products();
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="products-index">
            <div class="product-tree">
                <?= Products::getTree() ?>
            </div>
        </div>
        <!-- Modal -->
        <div id="productModal" class="modal fade" role="dialog">
            <div class="modal-dialog">

                <?php $form = ActiveForm::begin([
                    'id' => 'update-product'
                ]); ?>
                <?= $form->errorSummary($model) ?>
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"></h4>
                    </div>
                    <div class="modal-body">
                        <div class="hidden">
                            <?= $form->field($model, 'id')->textInput(['value' => $model->id]) ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'price_start')->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($model, 'price_end')->textInput(['maxlength' => true]) ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'alternative_price')->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($model, 'alternative_date')->widget(DatePicker::classname(), [
                                    'type' => DatePicker::TYPE_INPUT,
                                    'options' => ['placeholder' => 'Выберите дату'],
                                    'pluginOptions' => [
                                        'autoclose' => true,
                                        'format' => 'yyyy-mm-dd',
                                        'todayHighlight' => true
                                    ]
                                ]) ?>
                            </div>
                        </div>

                        <?= $form->field($model, 'delta')->textInput(['maxlength' => true]) ?>

                        <?= $form->field($model, 'minBalance')->textInput(['maxlength' => true]) ?>

                        <?= $form->field($model, 'zone')->dropDownList(Zone::getList(), ['prompt' => 'Выберите зону']) ?>

                        <?= $form->field($model, 'showOnReport')->checkbox() ?>

                        <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
                    </div>
                    <div class="modal-footer">
                        <div class="form-group">
                            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>