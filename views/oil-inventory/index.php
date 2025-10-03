<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\OilInventory;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OilInventorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Учет масла';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="oil-inventory-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать запись', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin() ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'created_at',
                'label' => 'Дата',
                'format' => ['date', 'php:d.m.Y H:i'],
                'filter' => false,
            ],

            [
                'attribute' => 'income',
                'label' => 'Приход (л)',
                'format' => ['decimal', 3],
                'contentOptions' => ['class' => 'text-right'],
            ],

            [
                'attribute' => 'return_amount_kg',
                'label' => 'Возврат (кг)',
                'format' => 'raw',
                'value' => function ($model) {
                    return number_format($model->return_amount_kg, 3) . ' кг<br><small class="text-muted">(' . number_format($model->return_amount, 3) . ' л)</small>';
                },
                'contentOptions' => ['class' => 'text-right'],
            ],

            [
                'attribute' => 'apparatus',
                'label' => 'Аппарат (л)',
                'format' => ['decimal', 3],
                'contentOptions' => ['class' => 'text-right'],
            ],

            [
                'attribute' => 'new_oil',
                'label' => 'Новое масло (л)',
                'format' => ['decimal', 3],
                'contentOptions' => ['class' => 'text-right'],
            ],

            [
                'attribute' => 'status',
                'label' => 'Статус',
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
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'status',
                    ['' => 'Все статусы'] + OilInventory::getStatusList(),
                    ['class' => 'form-control']
                ),
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {print}',
                'buttons' => [
                    'update' => function ($url, $model, $key) {
                        if (!$model->canEdit()) {
                            $reason = Html::encode($model->getEditRestrictionReason());
                            return Html::tag('span', '<span class="glyphicon glyphicon-pencil"></span>', [
                                'class' => 'text-muted edit-restricted',
                                'data-toggle' => 'popover',
                                'data-trigger' => 'click',
                                'data-placement' => 'left',
                                'data-content' => $reason,
                                'tabindex' => '0',
                                'style' => 'cursor: pointer; opacity: 0.5;',
                            ]);
                        }
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                            'title' => 'Редактировать',
                            'data-pjax' => '0',
                        ]);
                    },
                    'print' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-print"></span>', ['print', 'id' => $model->id], [
                            'title' => 'Печать',
                            'target' => '_blank',
                            'data-pjax' => '0',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

    <?php Pjax::end() ?>

</div>

<?php
$this->registerJs(<<<JS
    $(document).on('pjax:end', function() {
        initPopovers();
    });

    function initPopovers() {
        $('.edit-restricted').popover({
            container: 'body',
            html: false
        });
    }

    // Инициализация при загрузке страницы
    initPopovers();
JS
);
?>

