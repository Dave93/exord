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
                        'type' => 'text',
                        'inputmode' => 'decimal',
                        'pattern' => '[0-9]+([\.\,][0-9]+)?',
                        'placeholder' => '0.000',
                        'class' => 'form-control numeric-field'
                    ]) ?>

                    <?= $form->field($model, 'return_amount_kg')->textInput([
                        'type' => 'text',
                        'inputmode' => 'decimal',
                        'pattern' => '[0-9]+([\.\,][0-9]+)?',
                        'placeholder' => '0.000',
                        'class' => 'form-control numeric-field',
                        'data-max' => '100'
                    ]) ?>

                    <?= Html::activeHiddenInput($model, 'return_amount') ?>

                    
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
                        'type' => 'text',
                        'inputmode' => 'decimal',
                        'pattern' => '[0-9]+([\.\,][0-9]+)?',
                        'placeholder' => '0.000',
                        'class' => 'form-control numeric-field'
                    ]) ?>

                    <?= $form->field($model, 'new_oil')->textInput([
                        'type' => 'text',
                        'inputmode' => 'decimal',
                        'pattern' => '[0-9]+([\.\,][0-9]+)?',
                        'placeholder' => '0.000',
                        'class' => 'form-control numeric-field'
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
    const numericFields = document.querySelectorAll('.numeric-field');
    numericFields.forEach(function(field) {
        // Разрешаем ввод цифр, точки и запятой
        field.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            const currentValue = e.target.value;
            
            // Разрешаем цифры
            if (/[0-9]/.test(char)) {
                return true;
            }
            
            // Разрешаем одну точку или запятую
            if ((char === '.' || char === ',') && !currentValue.includes('.') && !currentValue.includes(',')) {
                return true;
            }
            
            // Разрешаем управляющие клавиши
            if (e.which < 32) {
                return true;
            }
            
            e.preventDefault();
        });
        
        // При потере фокуса преобразуем запятую в точку и валидируем
        field.addEventListener('blur', function(e) {
            let value = e.target.value;
            
            // Заменяем запятую на точку для корректного сохранения в БД
            value = value.replace(',', '.');
            
            // Проверяем, что это валидное число
            if (value && !isNaN(value)) {
                const numValue = parseFloat(value);
                
                // Проверка максимального значения для поля возврата
                if (e.target.name === 'OilInventory[return_amount_kg]') {
                    const maxValue = parseFloat(e.target.getAttribute('data-max') || 100);
                    if (numValue > maxValue) {
                        value = maxValue.toString();
                        alert('Максимальное значение возврата: ' + maxValue + ' кг');
                    }
                }
                
                // Форматируем до 3 знаков после запятой
                e.target.value = numValue.toFixed(3);
            } else if (value) {
                // Если не число, очищаем поле
                e.target.value = '';
            }
        });
        
        // При отправке формы также преобразуем запятую в точку
        field.form && field.form.addEventListener('submit', function() {
            field.value = field.value.replace(',', '.');
        });
    });
});
</script> 