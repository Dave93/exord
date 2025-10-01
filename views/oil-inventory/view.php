<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\OilInventory;

/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */

$this->title = 'Запись учета масла #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Учет масла', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="oil-inventory-view">

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <?= Html::encode($this->title) ?>
                    </h3>
                    <div class="box-tools pull-right">
                        <?php if ($model->status !== OilInventory::STATUS_ACCEPTED): ?>
                            <?= Html::a('<i class="fa fa-pencil"></i> Редактировать', ['update', 'id' => $model->id], [
                                'class' => 'btn btn-primary btn-sm'
                            ]) ?>
                            <?= Html::a('<i class="fa fa-trash"></i> Удалить', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-danger btn-sm',
                                'data' => [
                                    'confirm' => 'Вы уверены, что хотите удалить эту запись?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        <?php endif; ?>
                        <?= Html::a('<i class="fa fa-print"></i> Печать', ['print', 'id' => $model->id], [
                            'class' => 'btn btn-default btn-sm',
                            'target' => '_blank',
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
                                'attribute' => 'income',
                                'label' => 'Приход (л)',
                                'format' => ['decimal', 3],
                            ],
                            [
                                'attribute' => 'return_amount_kg',
                                'label' => 'Возврат (кг)',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<strong>' . number_format($model->return_amount_kg, 3) . ' кг</strong><br><small class="text-muted">Конвертировано в литры: ' . number_format($model->return_amount, 3) . ' л</small>';
                                },
                            ],
                            [
                                'attribute' => 'apparatus',
                                'label' => 'Аппарат (л)',
                                'format' => ['decimal', 3],
                            ],
                            [
                                'attribute' => 'new_oil',
                                'label' => 'Новое масло (л)',
                                'format' => ['decimal', 3],
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $statusColors = [
                                        OilInventory::STATUS_NEW => 'label-info',
                                        OilInventory::STATUS_FILLED => 'label-warning',
                                        OilInventory::STATUS_REJECTED => 'label-danger',
                                        OilInventory::STATUS_ACCEPTED => 'label-success',
                                    ];

                                    $colorClass = isset($statusColors[$model->status]) ? $statusColors[$model->status] : 'label-default';

                                    return Html::tag('span', $model->getStatusLabel(), [
                                        'class' => 'label ' . $colorClass
                                    ]);
                                },
                            ],
                            [
                                'attribute' => 'created_at',
                                'format' => ['date', 'php:d.m.Y H:i:s'],
                            ],
                            [
                                'attribute' => 'updated_at',
                                'format' => ['date', 'php:d.m.Y H:i:s'],
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <p>
                <?= Html::a('<i class="fa fa-arrow-left"></i> Назад к списку', ['index'], ['class' => 'btn btn-default']) ?>
            </p>
        </div>
    </div>

</div>

<style>
.font-weight-bold {
    font-weight: bold !important;
}

.detail-view th {
    width: 30%;
}

.btn-group-vertical .btn {
    margin-bottom: 5px;
}
</style> 