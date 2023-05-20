<?php
/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $searchModel app\models\OrderItemSearch */

/* @var $dataProvider yii\data\ActiveDataProvider */

use app\models\User;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = "Заказ #" . $model->id;
$this->params['breadcrumbs'][] = $this->title;
$quantity = 0;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </div>
    <hr>
    <div class="content">
        <div class="table-responsive mb20">
            <? $form = ActiveForm::begin([
                'id' => 'filterForm',
                'method' => 'post',
                'action' => ['/invoice/view?id=' . $model->id]
            ]) ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
//            'filterModel' => $searchModel,
                'summary' => false,
                'tableOptions' => [
                    'class' => 'table table-hover table-striped',
                ],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'contentOptions' => [
                            'width' => 40,
                        ]
                    ],
                    [
                        'attribute' => 'productId',
                        'value' => function ($model) {
                            return $model->product->name;
                        }
                    ],
                    [
                        'label' => 'Ед. Изм.',
                        'value' => function ($model) {
                            return $model->product->mainUnit;
                        },
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'quantity',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center quantity'
                        ]
                    ],
                    [
                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'header' => 'Отправлен со склада',
                        'content' => function ($model) {
                            return '<div class="custom-control custom-checkbox">' . $model->shipped_from_warehouse . '</div>';
                        },
                    ],
                    [
                        'header' => 'Совпадает с приходом',
                        'attribute' => 'isFactSupplierQuantity',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'content' => function ($model) {
                            return '<div class="custom-control custom-checkbox">' . Html::checkBox('data[' . $model->productId . '][isFactSupplierQuantity]', false, ['id' => "'customCheck" . $model->productId . "'", 'class' => 'custom-control-input']) . '</div>';
                        },
                    ],
                    [
                        'class' => 'yii\grid\Column',
                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'header' => 'По факту',
                        'content' => function ($model) {
                            $inputName = 'data[' . $model->productId . '][factStoreQuantity]';
                            return Html::input('number', $inputName, $model->factStoreQuantity ?: 0, ['class' => 'form-control w130 factStoreQuantity',]);
                        }
                    ],
                ],
            ]); ?>

            <p class="pull-right">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
            </p>
            <? ActiveForm::end(); ?>
        </div>

    </div>
</div>

<script>
    $(document).ready(function () {
        $('input[type="checkbox"]').click(function () {
            if ($(this).is(":checked")) {
                $(this).closest('tr').find('input[type="number"]').prop('readonly', true);
                $(this).closest('tr').css('background-color', '#9efba5');
                const quantity = $(this).closest('tr').find('.quantity').text();
                $(this).closest('tr').find('.factStoreQuantity').val(quantity);
            } else if ($(this).is(":not(:checked)")) {
                $(this).closest('tr').find('input[type="number"]').prop('readonly', false);
                $(this).closest('tr').css('background-color', '#fff');
                $(this).closest('tr').find('.factStoreQuantity').val(0);
            }
        });
    });
</script>