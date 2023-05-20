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
                'action' => ['/invoice/office-view?id=' . $model->id]
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
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'contentOptions' => [
                            'class' => 'text-center quantity'
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
                        'attribute' => 'factStoreQuantity',
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'class' => 'yii\grid\Column',
                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'header' => 'Окончательное кол-во',
                        'content' => function ($model) {
                            $inputName = 'data[' . $model->productId . '][factOfficeQuantity]';
                            return Html::input('number', $inputName, $model->factOfficeQuantity ?: 0, ['class' => 'form-control w130 factOfficeQuantity',]);
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
        $('#filterForm').on('submit', function(e) {
            let rowsCount = 0;
            let emptyRowsCount = 0;
            $('.factOfficeQuantity').each(function(){
                rowsCount++;
            });

            $('.factOfficeQuantity').each(function(){
                if ($(this).val() == 0) {
                    emptyRowsCount++;
                }
            });

            if (rowsCount == emptyRowsCount) {
                e.preventDefault();
                e.stopPropagation();
                Toast.show({
                    message: 'Поле "Окончательное кол-во" не может быть пустым',
                    position: Toast.POSITION_BOTTOM,
                    background: '#ff0000',
                    icon: 'error',
                    hideAfter: 3500,
                    stack: 6
                })
            }
        });
    });
</script>