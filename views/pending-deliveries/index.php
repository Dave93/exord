<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;


/* @var $this yii\web\View */
?>

<?php
$this->title = 'Iiko Висячие заказы';
$this->params['breadcrumbs'][] = $this->title;
$statusText = [
    "Unconfirmed" => 'Не подтвержден',
    "WaitCooking" => 'Ожидание готовки',
    "ReadyForCooking" => 'Готово для готовки',
    "CookingStarted" => 'Готовится',
    "CookingCompleted" => 'Готово',
    "Waiting" => 'Ожидает',
    "OnWay" => 'В пути',
    "Delivered" => 'Доставлен',
    "Closed" => 'Закрыто',
    "Cancelled" => 'Отменен'
];

$removalTypes = [
    '' => 'Выберите списание',
    "3f578008-81f7-ace3-2462-4360ca311b43" => "Со списанием",
    "471e15a1-b635-a7b1-8886-1514364d11a8" => "Без списания"
];

$orgId = [
    "946888c6-87d|9bc50b70-7b35-493b-a9a1-c151ce35e28c" => "Les Ailes",
    "d4cf3d80-317|dbce4a9c-32a3-44c5-90a9-a4ca7168f8ac" => "Chopar Pizza"
];
$loginByOrganization = [
    '9bc50b70-7b35-493b-a9a1-c151ce35e28c' => '946888c6-87d',
    'dbce4a9c-32a3-44c5-90a9-a4ca7168f8ac' => 'd4cf3d80-317'
];
?>

<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content">
        <?php
        $form = ActiveForm::begin([
            'id' => 'filterForm',
            'method' => 'get',
            'action' => ['/pending-deliveries/index']
        ]);
        ?>
        <div class="row">
            <div class="input-daterange datepicker align-items-center" data-date-format="dd.mm.yyyy"
                 data-today-highlight="1">
                <div class="col-xs-2">
                    <div class="form-group">
                        <input name="start" class="form-control" placeholder="Дата с" type="text"
                               autocomplete="off"
                               value="<?= $start ?>"/>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="form-group">
                        <input name="end" class="form-control" placeholder="по" type="text"
                               autocomplete="off"
                               value="<?= $end ?>"/>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="form-group">
                        <select name="organization" class="form-control">
                            <?php
                            foreach ($orgId as $orgKey => $orgName) {
                                ?>
                                <option value="<?= $orgKey ?>" <?= ($organization == $orgKey ? 'selected' : '') ?>><?= $orgName ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-xs-2">
                    <div class="form-group">
                        <select name="terminal" class="form-control">
                            <option value="">Выберите филиал</option>
                            <?php
                            foreach ($terminals as $key => $terminalName) {
                                ?>
                                <option value="<?= $key ?>" <?= ($terminal == $key ? 'selected' : '') ?>><?= $terminalName ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-xs-2">
                <?= Html::submitButton('Показать', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>


        <?php

        //        echo '<pre>'; print_r($dataProvider); echo '</pre>';
        echo GridView::widget([
            'id' => 'my-grid-view',
            'dataProvider' => $dataProvider,
            'summary' => false,
            'tableOptions' => [
                'class' => 'table table-hover',
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' => 'Концепция',
                    'attribute' => 'conception',
                    'value' => function ($t) {
                        return $t['order']['conception']['name'];
                    }
                ],
                [
                    'label' => 'Тип заказа',
                    'attribute' => 'orderType',
                    'format' => 'raw',
                    'value' => function ($p) {
                        return $p['order']['orderType']['name'];
                    }
                ],
                [
                    'label' => 'Время',
                    'attribute' => 'timestamp',
                    'value' => function ($o) {
                        return $o["order"]["whenCreated"];
                    }
                ],
                [
                    'label' => 'Id онлайн заказа',
                    'attribute' => 'externalNumber'
                ],
                [
                    'label' => 'Номер чека',
                    'attribute' => 'number',
                    'value' => function ($n) {
                        return $n['order']['number'];
                    }
                ],
                [
                    'label' => 'Статус заказа',
                    'attribute' => 'status',
                    'value' => function ($n) use ($statusText) {

                        return $statusText[$n['order']['status']];
                    }
                ],
                [
                    'label' => 'Комментария',
                    'attribute' => 'comment',
                    'contentOptions' => ['style' => 'width: 200px;'],
                    'value' => function ($n) {
                        return $n['order']['comment'];
                    }
                ],
                [
                    'label' => 'Номер телефона',
                    'attribute' => 'phone',
                    'value' => function ($p) {
                        return $p['order']['phone'];
                    }
                ],
                [
                    'label' => 'Сумма',
                    'attribute' => 'sum',
                    'value' => function ($p) {
                        return Yii::$app->formatter->asCurrency($p['order']['sum'], 'UZS');
                    }
                ],
                [
                    'label' => 'Состав продуктов',
                    'attribute' => 'products',
                    'format' => 'raw',
                    'value' => function ($p) {
                        $html = '';
                        foreach ($p['order']['items'] as $item) {
                            $html .= "<b>" . $item['product']['name'] . "</b> x " . $item['amount'] . "<br />";
                        }

                        return $html;
                    }
                ],
                [
                    'label' => 'Тип оплаты',
                    'attribute' => 'payments',
                    'format' => 'raw',
                    'value' => function ($p) {
                        $html = '';
                        foreach ($p['order']['payments'] as $item) {
                            $html .= "<b>" . $item['paymentType']['name'] . "<br />";
                        }

                        return $html;
                    }
                ],
                [
                    'attribute' => 'Тип удаления',
                    'format' => 'raw',
                    'value' => function ($model) use ($removalTypes) {
                        return Html::dropDownList(
                            'removalType', // Assuming the model has an `id` attribute
                            $model->removalType,               // This is the current value
                            $removalTypes
                        );
                    },
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{delete}',
                    'contentOptions' => [
                        'width' => 60,
                        'class' => 'text-center'
                    ],
                    'buttons' => [
                        'delete' => function ($url, $model) use ($loginByOrganization) {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', [''], [
                                'class' => 'btn btn-danger btn-xs',
                                'onclick' => 'cancelOrder(event, "' . $model['id'] . '", "' . $model['organizationId'] . '", "' . $loginByOrganization[$model['organizationId']] . '"); return false;',
                            ]);
                        },
                    ],
                ],
            ],
        ]);
        ?>
    </div>
</div>

<script>
    function cancelOrder(e, orderId, organizationId, login) {
        window.yii.confirm('Вы уверены, что хотите удалить?', () => {
            const removeType = $(e.target).closest('tr').find('[name=removalType]').val();
            if (removeType) {
                location.href = '/pending-deliveries/cancel-order?orderId=' + orderId + '&organizationId=' + organizationId + '&removalTypeId=' + removeType + '&login=' + login;
            } else {
                alert("Не выбрали метод списания");
            }
        });
    }
</script>