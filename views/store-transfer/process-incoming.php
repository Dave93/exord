<?php

use app\models\StoreTransferItem;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\StoreTransfer */
/* @var $items app\models\StoreTransferItem[] */

$this->title = 'Обработка заявки №' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Внутренние перемещения', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Входящие заявки', 'url' => ['incoming']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(<<<JS
    // Автозаполнение поля "Передано" значением из "Запрошено"
    $('.copy-requested').on('click', function(e) {
        e.preventDefault();
        var itemId = $(this).data('item-id');
        var requestedQty = $(this).data('requested-qty');
        $('#transferred-' + itemId).val(requestedQty);
    });

    // Установить 0 для всех позиций (отказать)
    $('#reject-all').on('click', function(e) {
        e.preventDefault();
        if (confirm('Вы уверены, что хотите отказать по всем позициям?')) {
            $('.transferred-quantity').val(0);
        }
    });

    // Копировать запрошенное количество для всех позиций
    $('#accept-all').on('click', function(e) {
        e.preventDefault();
        $('.copy-requested').each(function() {
            var itemId = $(this).data('item-id');
            var requestedQty = $(this).data('requested-qty');
            $('#transferred-' + itemId).val(requestedQty);
        });
    });
JS
);
?>

<div class="card">
    <div class="header">
        <h4 class="title"><?= Html::encode($this->title) ?></h4>
        <p class="category">
            Филиал-получатель: <strong><?= Html::encode($model->requestStore->name) ?></strong>
            <br>
            Дата создания: <?= Yii::$app->formatter->asDatetime($model->created_at, 'php:d.m.Y H:i') ?>
        </p>
        <?php if ($model->comment): ?>
            <div class="alert alert-info" style="margin-top: 15px;">
                <strong>Комментарий:</strong> <?= Html::encode($model->comment) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="content">
        <?php $form = ActiveForm::begin([
            'action' => ['confirm-transfer', 'id' => $model->id],
            'method' => 'post',
        ]); ?>

        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-12">
                <div class="btn-group pull-right">
                    <?= Html::button(
                        '<span class="glyphicon glyphicon-ok"></span> Принять все',
                        ['class' => 'btn btn-success btn-fill btn-sm', 'id' => 'accept-all']
                    ) ?>
                    <?= Html::button(
                        '<span class="glyphicon glyphicon-remove"></span> Отказать по всем',
                        ['class' => 'btn btn-danger btn-fill btn-sm', 'id' => 'reject-all']
                    ) ?>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Продукт</th>
                        <th style="width: 150px;" class="text-center">Запрошено</th>
                        <th style="width: 200px;" class="text-center">Передаваемое количество</th>
                        <th style="width: 100px;" class="text-center">Действие</th>
                        <th style="width: 100px;" class="text-center">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= Html::encode($item->product->name) ?></strong>
                                <?php if ($item->product->num): ?>
                                    <br>
                                    <small class="text-muted">Артикул: <?= Html::encode($item->product->num) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="label label-info" style="font-size: 14px;">
                                    <?= Yii::$app->formatter->asDecimal($item->requested_quantity, 2) ?>
                                    <?= Html::encode($item->product->getUnit()) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($item->item_status === StoreTransferItem::STATUS_PENDING): ?>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <?= Html::input(
                                            'number',
                                            'transferred_quantities[' . $item->id . ']',
                                            $item->requested_quantity,
                                            [
                                                'class' => 'form-control text-center transferred-quantity',
                                                'id' => 'transferred-' . $item->id,
                                                'step' => '0.01',
                                                'min' => '0',
                                                'placeholder' => 'Введите количество',
                                                'style' => 'font-weight: bold;'
                                            ]
                                        ) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="label label-default">
                                        <?= $item->transferred_quantity ? Yii::$app->formatter->asDecimal($item->transferred_quantity, 2) : '0' ?>
                                        <?= Html::encode($item->product->getUnit()) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($item->item_status === StoreTransferItem::STATUS_PENDING): ?>
                                    <?= Html::button(
                                        '<span class="glyphicon glyphicon-arrow-left"></span>',
                                        [
                                            'class' => 'btn btn-primary btn-sm copy-requested',
                                            'data-item-id' => $item->id,
                                            'data-requested-qty' => $item->requested_quantity,
                                            'title' => 'Скопировать запрошенное количество',
                                        ]
                                    ) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $statusClass = [
                                        StoreTransferItem::STATUS_PENDING => 'label-warning',
                                        StoreTransferItem::STATUS_TRANSFERRED => 'label-success',
                                        StoreTransferItem::STATUS_REJECTED => 'label-danger',
                                        StoreTransferItem::STATUS_APPROVED => 'label-info',
                                    ];
                                ?>
                                <span class="label <?= $statusClass[$item->item_status] ?? 'label-default' ?>">
                                    <?= $item->getStatusLabel() ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <div class="form-group">
                    <?= Html::submitButton(
                        '<span class="glyphicon glyphicon-ok"></span> Подтвердить передачу',
                        ['class' => 'btn btn-success btn-fill']
                    ) ?>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-arrow-left"></span> Назад',
                        ['incoming'],
                        ['class' => 'btn btn-default']
                    ) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<div class="card">
    <div class="header">
        <h4 class="title">Инструкция</h4>
    </div>
    <div class="content">
        <ul>
            <li><strong>Передаваемое количество</strong> - укажите фактическое количество товара, которое вы готовы передать</li>
            <li>Используйте кнопку <span class="glyphicon glyphicon-arrow-left"></span> для копирования запрошенного количества</li>
            <li>Установите <strong>0</strong>, если не можете передать данную позицию (она будет отклонена)</li>
            <li>Используйте кнопки "Принять все" или "Отказать по всем" для массового действия</li>
            <li>После нажатия "Подтвердить передачу" изменить данные будет невозможно</li>
        </ul>
    </div>
</div>
