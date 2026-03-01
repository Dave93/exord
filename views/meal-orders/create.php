<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MealOrders */
/* @var $dishes app\models\Dishes[] */
/* @var $existingItems array */

$isUpdate = !$model->isNewRecord;
$this->title = $isUpdate
    ? "Редактировать заказ блюд #" . $model->id
    : "Заказ блюд: " . ($model->store ? $model->store->name : '') . ' на ' . date("d.m.Y", strtotime($model->date));
$this->params['breadcrumbs'][] = $this->title;

if (!isset($existingItems)) {
    $existingItems = [];
}

$js = <<<JS
$("#searchField").on("keyup", function() {
    let value = $(this).val().toLowerCase();
    $(".meal-order-table tbody tr").filter(function() {
        $(this).toggle($(this).find('td:first').text().toLowerCase().includes(value));
    });
});

$('#meal-order-form').on('beforeSubmit', function(e) {
    var isFormValid = false;
    $("input.quantity").each(function() {
        if ($.trim($(this).val()).length != 0 && parseFloat($(this).val()) > 0) {
            isFormValid = true;
        }
    });
    if (!isFormValid && $("input.quantity").length > 0) {
        alert('Пожалуйста выберите блюда для заказа!');
        return false;
    }
    return true;
});

$(document).on("keydown", "#meal-order-form", function(event) {
    return event.keyCode != 13;
});

window.isSubmitting = false;
$(document).on('submit', '#meal-order-form', function(e) {
    if (window.isSubmitting) {
        e.stopPropagation();
        e.preventDefault();
        return false;
    }
    if (!window.isSubmitting) {
        window.isSubmitting = true;
        return true;
    }
});
JS;

$this->registerJs($js);
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="orders-list">
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-6">
                    <h4 class="title" style="padding-bottom: 0">Блюда</h4>
                </div>
            </div>
            <?= Html::textInput('search', null, ['id' => 'searchField', 'class' => 'form-control', 'placeholder' => 'Введите название блюда']) ?>
            <hr>
            <?php $form = ActiveForm::begin([
                'id' => 'meal-order-form'
            ]); ?>
            <table class="table table-hover table-striped meal-order-table">
                <thead>
                <tr>
                    <th>Наименование</th>
                    <th class="text-center">Ед. изм.</th>
                    <th>Количество</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($dishes as $dish): ?>
                    <?php $existingQty = isset($existingItems[$dish->id]) ? $existingItems[$dish->id] : ''; ?>
                    <tr>
                        <td><?= Html::encode($dish->name) ?></td>
                        <td width="100" class="text-center"><?= Html::encode($dish->unit) ?></td>
                        <td width="200">
                            <input type="number" class="form-control quantity"
                                   name="Items[<?= $dish->id ?>]"
                                   value="<?= $existingQty ?>"
                                   step="any"/>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>
            <hr>
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
