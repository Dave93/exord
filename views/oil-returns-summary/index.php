<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

$this->title = 'Сводка возврата масла по филиалам';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="oil-returns-summary-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="well">
        <?php $form = ActiveForm::begin(['method' => 'get']); ?>
        
        <div class="row">
            <div class="col-md-4">
                <?= DatePicker::widget([
                    'name' => 'date_from',
                    'value' => $dateFrom,
                    'type' => DatePicker::TYPE_INPUT,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                        'todayHighlight' => true,
                    ],
                    'options' => [
                        'placeholder' => 'Дата с',
                    ]
                ]); ?>
            </div>
            
            <div class="col-md-4">
                <?= DatePicker::widget([
                    'name' => 'date_to',
                    'value' => $dateTo,
                    'type' => DatePicker::TYPE_INPUT,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                        'todayHighlight' => true,
                    ],
                    'options' => [
                        'placeholder' => 'Дата по',
                    ]
                ]); ?>
            </div>
            
            <div class="col-md-4">
                <?= Html::submitButton('Применить фильтр', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                Период: <?= Yii::$app->formatter->asDate($dateFrom, 'long') ?> - <?= Yii::$app->formatter->asDate($dateTo, 'long') ?>
            </h3>
        </div>
        <div class="panel-body">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Филиал</th>
                        <th>Сумма возврата масла (кг)</th>
                        <th>Сумма возврата масла (л)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?= Html::encode($row['terminal_name'] ?: 'Без названия') ?></td>
                                <td class="text-right">
                                    <strong><?= Yii::$app->formatter->asDecimal($row['total_return_kg'], 2) ?> кг</strong>
                                </td>
                                <td class="text-right">
                                    <?= Yii::$app->formatter->asDecimal($row['total_return_liters'], 2) ?> л
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="info">
                            <td><strong>Итого:</strong></td>
                            <td class="text-right">
                                <strong><?= Yii::$app->formatter->asDecimal($totalReturnKg, 2) ?> кг</strong>
                            </td>
                            <td class="text-right">
                                <strong><?= Yii::$app->formatter->asDecimal($totalReturnLiters, 2) ?> л</strong>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Нет данных за выбранный период</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>