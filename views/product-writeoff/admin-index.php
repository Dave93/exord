<?php

use app\models\ProductWriteoff;
use app\models\Stores;
use kartik\date\DatePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ProductWriteoffSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Управление списаниями';
$this->params['breadcrumbs'][] = $this->title;

// Получаем список магазинов для фильтра
$stores = ArrayHelper::map(Stores::find()->all(), 'id', 'name');
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content">
        <!-- Форма фильтров -->
        <div class="filter-form" style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <?php $form = ActiveForm::begin([
                'action' => ['admin-index'],
                'method' => 'get',
            ]); ?>

            <div class="row">
                <div class="col-md-3">
                    <label>Магазин</label>
                    <?= Html::activeDropDownList(
                        $searchModel,
                        'store_id',
                        $stores,
                        ['class' => 'form-control', 'prompt' => 'Все магазины']
                    ) ?>
                </div>
                <div class="col-md-2">
                    <label>Дата с</label>
                    <?= DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date_from',
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true,
                        ],
                        'options' => [
                            'class' => 'form-control',
                            'placeholder' => 'От'
                        ]
                    ]) ?>
                </div>
                <div class="col-md-2">
                    <label>Дата по</label>
                    <?= DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'date_to',
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true,
                        ],
                        'options' => [
                            'class' => 'form-control',
                            'placeholder' => 'До'
                        ]
                    ]) ?>
                </div>
                <div class="col-md-2">
                    <label>Статус</label>
                    <?= Html::activeDropDownList(
                        $searchModel,
                        'status',
                        [
                            ProductWriteoff::STATUS_NEW => 'Новое',
                            ProductWriteoff::STATUS_APPROVED => 'Утверждено',
                        ],
                        ['class' => 'form-control', 'prompt' => 'Все статусы']
                    ) ?>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label><br>
                    <?= Html::submitButton('Применить', ['class' => 'btn btn-primary btn-fill']) ?>
                    <?= Html::a('Сбросить', ['admin-index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>

            <?php ActiveForm::end(); ?>
        </div>

        <!-- Таблица -->
        <div class="table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => false,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover',
            ],
            'rowOptions' => function ($model) {
                $class = "";
                if ($model->status == ProductWriteoff::STATUS_NEW)
                    $class = "bg-orange";
                elseif ($model->status == ProductWriteoff::STATUS_APPROVED)
                    $class = "bg-green";
                return ['class' => $class];
            },
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'contentOptions' => [
                        'width' => 40,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'id',
                    'format' => 'html',
                    'value' => function ($model) {
                        return Html::a($model->id, ['view', 'id' => $model->id]);
                    },
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'store_id',
                    'label' => 'Магазин',
                    'value' => function ($model) {
                        return $model->store ? $model->store->name : '-';
                    },
                    'contentOptions' => [
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'Дата создания',
                    'value' => function ($model) {
                        return date("d.m.Y H:i", strtotime($model->created_at));
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'label' => 'Кол. позиций',
                    'value' => function ($model) {
                        return $model->getItemsCount();
                    },
                    'headerOptions' => [
                        'class' => 'text-center'
                    ],
                    'contentOptions' => [
                        'width' => 100,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'created_by',
                    'label' => 'Создал',
                    'value' => function ($model) {
                        return $model->createdBy ? $model->createdBy->fullname : '-';
                    },
                    'contentOptions' => [
                        'width' => 120,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Статус',
                    'value' => function ($model) {
                        return $model->getStatusLabel();
                    },
                    'contentOptions' => [
                        'width' => 100,
                        'class' => 'text-center'
                    ]
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {approve} {delete}',
                    'buttons' => [
                        'approve' => function ($url, $model, $key) {
                            if ($model->status === ProductWriteoff::STATUS_NEW) {
                                return Html::a(
                                    '<span class="glyphicon glyphicon-ok"></span>',
                                    ['approve-form', 'id' => $model->id],
                                    ['title' => 'Утвердить списание']
                                );
                            }
                            return '';
                        },
                        'delete' => function ($url, $model, $key) {
                            if ($model->status === ProductWriteoff::STATUS_NEW) {
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                                    'title' => 'Удалить',
                                    'data' => [
                                        'confirm' => 'Вы уверены, что хотите удалить это списание?',
                                        'method' => 'post',
                                    ],
                                ]);
                            }
                            return '';
                        },
                    ],
                    'contentOptions' => [
                        'width' => 80,
                        'class' => 'text-center'
                    ]
                ],
            ],
        ]); ?>
        </div>
    </div>
</div>
