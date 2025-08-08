<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\OilInventory;

/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */

$this->title = 'Рассмотрение записи учета масла #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Учет масла', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Заполненные записи', 'url' => ['filled']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="oil-inventory-review">

    <div class="row">
        <div class="col-md-8">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-clipboard-check"></i> <?= Html::encode($this->title) ?>
                    </h3>
                    <div class="box-tools pull-right">
                        <?= Html::tag('span', $model->getStatusLabel(), [
                            'class' => 'label label-warning'
                        ]) ?>
                    </div>
                </div>

                <div class="box-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-striped table-bordered detail-view'],
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'store_id',
                                'label' => 'Магазин',
                                'value' => function ($model) {
                                    return $model->store ? $model->store->name : $model->store_id;
                                },
                            ],
                            [
                                'attribute' => 'created_at',
                                'label' => 'Дата создания',
                                'format' => ['date', 'php:d.m.Y H:i:s'],
                            ],
                            [
                                'attribute' => 'opening_balance',
                                'label' => 'Остаток на начало дня',
                                'format' => 'raw',
                                'value' => number_format($model->opening_balance, 3) . ' л',
                            ],
                            [
                                'attribute' => 'income',
                                'label' => 'Приход',
                                'format' => 'raw',
                                'value' => number_format($model->income, 3) . ' л',
                            ],
                            [
                                'attribute' => 'return_amount',
                                'label' => 'Возврат',
                                'format' => 'raw',
                                'value' => number_format($model->return_amount, 3) . ' л',
                            ],
                            [
                                'attribute' => 'apparatus',
                                'label' => 'Аппарат',
                                'format' => 'raw',
                                'value' => number_format($model->apparatus, 3) . ' л',
                            ],
                            [
                                'attribute' => 'new_oil',
                                'label' => 'Новое масло',
                                'format' => 'raw',
                                'value' => number_format($model->new_oil, 3) . ' л',
                            ],
                            [
                                'attribute' => 'evaporation',
                                'label' => 'Испарение',
                                'format' => 'raw',
                                'value' => number_format($model->evaporation, 3) . ' л',
                            ],
                            [
                                'attribute' => 'closing_balance',
                                'label' => 'Остаток на конец дня',
                                'format' => 'raw',
                                'value' => number_format($model->closing_balance, 3) . ' л',
                                'captionOptions' => ['style' => 'font-weight: bold;'],
                                'contentOptions' => ['style' => 'font-weight: bold; font-size: 16px;'],
                            ],
                            [
                                'attribute' => 'updated_at',
                                'label' => 'Последнее обновление',
                                'format' => ['date', 'php:d.m.Y H:i:s'],
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-calculator"></i> Расчет остатка
                    </h3>
                </div>
                <div class="box-body">
                    <div class="calculation-details">
                        <table class="table table-condensed">
                            <tr>
                                <td>Остаток на начало:</td>
                                <td class="text-right"><?= number_format($model->opening_balance, 3) ?> л</td>
                            </tr>
                            <tr class="success">
                                <td>+ Приход:</td>
                                <td class="text-right">+<?= number_format($model->income, 3) ?> л</td>
                            </tr>
                            <tr class="warning">
                                <td>- Возврат:</td>
                                <td class="text-right">-<?= number_format($model->return_amount, 3) ?> л</td>
                            </tr>
                            <tr class="warning">
                                <td>- Аппарат:</td>
                                <td class="text-right">-<?= number_format($model->apparatus, 3) ?> л</td>
                            </tr>
                            <tr class="warning">
                                <td>- Новое масло:</td>
                                <td class="text-right">-<?= number_format($model->new_oil, 3) ?> л</td>
                            </tr>
                            <tr class="info" style="border-top: 1px solid #ddd;">
                                <td><em>= Испарение (авто):</em></td>
                                <td class="text-right"><em><?= number_format($model->evaporation, 3) ?> л</em></td>
                            </tr>
                            <tr class="warning">
                                <td>- Испарение:</td>
                                <td class="text-right">-<?= number_format($model->evaporation, 3) ?> л</td>
                            </tr>
                            <tr class="active" style="border-top: 2px solid #ddd;">
                                <td><strong>Остаток на конец:</strong></td>
                                <td class="text-right"><strong><?= number_format($model->closing_balance, 3) ?> л</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-gavel"></i> Действия по записи
                    </h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Внимание!</strong> После принятия или отклонения записи её статус изменится и она не будет доступна для повторного рассмотрения.
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?= Html::a('<i class="fa fa-check"></i> Принять запись', ['approve', 'id' => $model->id], [
                                'class' => 'btn btn-success btn-block btn-lg',
                                'style' => 'margin-bottom: 10px;',
                                'data' => [
                                    'confirm' => 'Вы уверены, что хотите принять эту запись? Статус изменится на "Принят".',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?= Html::a('<i class="fa fa-times"></i> Отклонить запись', ['reject', 'id' => $model->id], [
                                'class' => 'btn btn-danger btn-block btn-lg',
                                'style' => 'margin-bottom: 15px;',
                                'data' => [
                                    'confirm' => 'Вы уверены, что хотите отклонить эту запись? Статус изменится на "Отклонён".',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= Html::a('<i class="fa fa-arrow-left"></i> К списку', ['filled'], [
                                'class' => 'btn btn-default btn-block'
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= Html::a('<i class="fa fa-eye"></i> Просмотр', ['view', 'id' => $model->id], [
                                'class' => 'btn btn-info btn-block'
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.detail-view th {
    width: 40%;
    background-color: #f9f9f9;
}

.detail-view td {
    padding: 12px 8px;
}

.calculation-details table {
    margin-bottom: 0;
}

.calculation-details tr td {
    padding: 8px;
    border-top: 1px solid #ddd;
}

.calculation-details tr:first-child td {
    border-top: none;
}

.btn-lg {
    padding: 10px 16px;
    font-size: 16px;
    line-height: 1.3333333;
    border-radius: 6px;
}

.alert {
    margin-bottom: 15px;
}

.box {
    margin-bottom: 20px;
}
</style> 