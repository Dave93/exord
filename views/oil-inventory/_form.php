<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\OilInventory;

/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="oil-inventory-form">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-sm-9\">{input}\n{error}</div>",
            'labelOptions' => ['class' => 'col-sm-3 control-label'],
        ],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Основные данные</h3>
                </div>
                <div class="box-body">
                    
                    <?= Html::activeHiddenInput($model, 'opening_balance') ?>

                    <?= $form->field($model, 'income')->textInput([
                        'type' => 'number',
                        'step' => '0.001',
                        'min' => '0',
                        'placeholder' => '0.000'
                    ]) ?>

                    <?= $form->field($model, 'return_amount_kg')->textInput([
                        'type' => 'number',
                        'step' => '0.001',
                        'min' => '0',
                        'max' => '100',
                        'placeholder' => '0.000'
                    ]) ?>

                    <?= Html::activeHiddenInput($model, 'return_amount') ?>

                    <?php if ($model->isNewRecord): ?>
                        <?= Html::activeHiddenInput($model, 'status') ?>
                    <?php else: ?>
                        <?= $form->field($model, 'status')->dropDownList(
                            OilInventory::getStatusList(),
                            ['class' => 'form-control']
                        ) ?>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Расходы</h3>
                </div>
                <div class="box-body">
                    
                    <?= $form->field($model, 'apparatus')->textInput([
                        'type' => 'number',
                        'step' => '0.001',
                        'min' => '0',
                        'placeholder' => '0.000'
                    ]) ?>

                    <?= $form->field($model, 'new_oil')->textInput([
                        'type' => 'number',
                        'step' => '0.001',
                        'min' => '0',
                        'placeholder' => '0.000'
                    ]) ?>

                    <?= Html::activeHiddenInput($model, 'evaporation') ?>


                    
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
            <?= Html::submitButton($model->isNewRecord ? 'Создать' : 'Обновить', [
                'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'
            ]) ?>
            <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>



<style>
.form-control-static {
    padding-top: 7px;
    padding-bottom: 7px;
    margin-bottom: 0;
    min-height: 34px;
}

.box {
    margin-bottom: 20px;
}

.form-horizontal .form-group {
    margin-left: 0;
    margin-right: 0;
}

.conversion-info {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
</style>

<script>
// Добавляем информацию о конвертации и автозамену запятой на точку
document.addEventListener('DOMContentLoaded', function() {
    // Информация о конвертации для поля возврата
    const kgField = document.querySelector('[name="OilInventory[return_amount_kg]"]').closest('.form-group');
    if (kgField) {
        const conversionInfo = document.createElement('div');
        conversionInfo.className = 'conversion-info';
        conversionInfo.innerHTML = '<i class="fa fa-info-circle"></i> Коэффициент конвертации: 1 кг ≈ 1.1 л (автоматически конвертируется в расчётах). Максимум: 100 кг';
        kgField.appendChild(conversionInfo);
    }
    
    // Автозамена запятой на точку для всех числовых полей
    const numericFields = document.querySelectorAll('input[type="number"]');
    numericFields.forEach(function(field) {
        // При вводе заменяем запятую на точку
        field.addEventListener('input', function(e) {
            let value = e.target.value;
            // Заменяем запятую на точку
            value = value.replace(',', '.');
            e.target.value = value;
        });
        
        // При вставке текста также заменяем запятую на точку
        field.addEventListener('paste', function(e) {
            e.preventDefault();
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            paste = paste.replace(',', '.');
            e.target.value = paste;
        });
        
        // Дополнительная проверка при потере фокуса
        field.addEventListener('blur', function(e) {
            let value = e.target.value;
            value = value.replace(',', '.');
            e.target.value = value;
            
            // Проверка ограничения для поля возврата
            if (e.target.name === 'OilInventory[return_amount_kg]') {
                const numValue = parseFloat(value);
                if (numValue > 100) {
                    e.target.value = '100';
                    alert('Максимальное значение возврата: 100 кг');
                }
            }
        });
    });
});
</script> 