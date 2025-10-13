<?php

use app\models\StoreTransfer;
use app\models\StoreTransferItem;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\StoreTransfer */
/* @var $itemsByStore array */

$this->title = 'Утверждение заявки #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Внутренние перемещения', 'url' => ['admin-index']];
$this->params['breadcrumbs'][] = ['label' => 'Заявка #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Утверждение';

$this->registerJs(<<<JS
    // Копировать переданное количество в утвержденное
    $('.copy-transferred').on('click', function(e) {
        e.preventDefault();
        var itemId = $(this).data('item-id');
        var transferredQty = $(this).data('transferred-qty');
        $('#approved-' + itemId).val(transferredQty);
        $('#status-' + itemId).val('approved');
    });

    // Копировать запрошенное количество в утвержденное
    $('.copy-requested').on('click', function(e) {
        e.preventDefault();
        var itemId = $(this).data('item-id');
        var requestedQty = $(this).data('requested-qty');
        $('#approved-' + itemId).val(requestedQty);
        $('#status-' + itemId).val('approved');
    });

    // Утвердить все переданные позиции
    $('#approve-all-transferred').on('click', function(e) {
        e.preventDefault();
        if (confirm('Утвердить все переданные количества?')) {
            $('.copy-transferred').each(function() {
                var itemId = $(this).data('item-id');
                var transferredQty = $(this).data('transferred-qty');
                if (transferredQty > 0) {
                    $('#approved-' + itemId).val(transferredQty);
                    $('#status-' + itemId).val('approved');
                }
            });
        }
    });

    // Отклонить все
    $('#reject-all').on('click', function(e) {
        e.preventDefault();
        if (confirm('Отклонить все позиции?')) {
            $('.item-status').val('rejected');
            $('.approved-quantity').val(0);
        }
    });

    // Изменение статуса позиции
    $('.item-status').on('change', function() {
        var itemId = $(this).data('item-id');
        if ($(this).val() === 'rejected') {
            $('#approved-' + itemId).val(0);
        }
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
            <br>
            Статус:
            <?php
                $statusClass = [
                    StoreTransfer::STATUS_NEW => 'label-primary',
                    StoreTransfer::STATUS_IN_PROGRESS => 'label-warning',
                    StoreTransfer::STATUS_COMPLETED => 'label-success',
                    StoreTransfer::STATUS_CANCELLED => 'label-default',
                ];
            ?>
            <span class="label <?= $statusClass[$model->status] ?? 'label-default' ?>">
                <?= $model->getStatusLabel() ?>
            </span>
        </p>
        <?php if ($model->comment): ?>
            <div class="alert alert-info" style="margin-top: 15px;">
                <strong>Комментарий:</strong> <?= Html::encode($model->comment) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="content">
        <?php $form = ActiveForm::begin([
            'action' => ['final-approve', 'id' => $model->id],
            'method' => 'post',
        ]); ?>

        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-12">
                <div class="btn-group pull-right">
                    <?= Html::button(
                        '<span class="glyphicon glyphicon-ok"></span> Утвердить все переданные',
                        ['class' => 'btn btn-success btn-fill btn-sm', 'id' => 'approve-all-transferred']
                    ) ?>
                    <?= Html::button(
                        '<span class="glyphicon glyphicon-remove"></span> Отклонить все',
                        ['class' => 'btn btn-danger btn-fill btn-sm', 'id' => 'reject-all']
                    ) ?>
                </div>
            </div>
        </div>

        <?php foreach ($itemsByStore as $storeId => $data): ?>
            <div style="border: 2px solid #e3e3e3; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f9f9f9;">
                <h5 style="font-size: 16px; font-weight: 600; margin-bottom: 15px;">
                    Филиал-источник: <?= Html::encode($data['store']->name) ?>
                </h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Продукт</th>
                                <th class="text-center" style="width: 120px;">Запрошено</th>
                                <th class="text-center" style="width: 120px;">Передано</th>
                                <th class="text-center" style="width: 180px;">Утвердить количество</th>
                                <th class="text-center" style="width: 120px;">Действия</th>
                                <th class="text-center" style="width: 150px;">Решение</th>
                                <th class="text-center" style="width: 100px;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1; ?>
                            <?php foreach ($data['items'] as $item): ?>
                                <tr>
                                    <td><?= $index++ ?></td>
                                    <td>
                                        <strong><?= Html::encode($item->product->name) ?></strong>
                                        <?php if ($item->product->num): ?>
                                            <br>
                                            <small class="text-muted">Артикул: <?= Html::encode($item->product->num) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="label label-info">
                                            <?= Yii::$app->formatter->asDecimal($item->requested_quantity, 2) ?>
                                            <?= Html::encode($item->product->getUnit()) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->transferred_quantity !== null): ?>
                                            <span class="label <?= $item->transferred_quantity > 0 ? 'label-success' : 'label-danger' ?>">
                                                <?= Yii::$app->formatter->asDecimal($item->transferred_quantity, 2) ?>
                                                <?= Html::encode($item->product->getUnit()) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="label label-default">Не передано</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->item_status !== StoreTransferItem::STATUS_APPROVED): ?>
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <?= Html::input(
                                                    'number',
                                                    'approved_quantities[' . $item->id . ']',
                                                    $item->transferred_quantity ?? $item->requested_quantity,
                                                    [
                                                        'class' => 'form-control text-center approved-quantity',
                                                        'id' => 'approved-' . $item->id,
                                                        'step' => '0.01',
                                                        'min' => '0',
                                                        'placeholder' => 'Количество',
                                                        'style' => 'font-weight: bold;'
                                                    ]
                                                ) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="label label-success">
                                                <?= Yii::$app->formatter->asDecimal($item->approved_quantity, 2) ?>
                                                <?= Html::encode($item->product->getUnit()) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->item_status !== StoreTransferItem::STATUS_APPROVED): ?>
                                            <?php if ($item->transferred_quantity !== null && $item->transferred_quantity > 0): ?>
                                                <?= Html::button(
                                                    '<span class="glyphicon glyphicon-arrow-left"></span>',
                                                    [
                                                        'class' => 'btn btn-success btn-xs copy-transferred',
                                                        'data-item-id' => $item->id,
                                                        'data-transferred-qty' => $item->transferred_quantity,
                                                        'title' => 'Копировать переданное',
                                                    ]
                                                ) ?>
                                            <?php endif; ?>
                                            <?= Html::button(
                                                '<span class="glyphicon glyphicon-arrow-up"></span>',
                                                [
                                                    'class' => 'btn btn-primary btn-xs copy-requested',
                                                    'data-item-id' => $item->id,
                                                    'data-requested-qty' => $item->requested_quantity,
                                                    'title' => 'Копировать запрошенное',
                                                ]
                                            ) ?>
                                        <?php else: ?>
                                            <span class="text-success">✓</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->item_status !== StoreTransferItem::STATUS_APPROVED): ?>
                                            <?= Html::dropDownList(
                                                'item_statuses[' . $item->id . ']',
                                                $item->item_status === StoreTransferItem::STATUS_TRANSFERRED ? 'approved' : 'pending',
                                                [
                                                    'pending' => 'Не решено',
                                                    'approved' => 'Утвердить',
                                                    'rejected' => 'Отклонить',
                                                ],
                                                [
                                                    'class' => 'form-control item-status',
                                                    'id' => 'status-' . $item->id,
                                                    'data-item-id' => $item->id,
                                                ]
                                            ) ?>
                                        <?php else: ?>
                                            <span class="label label-success">Утверждено</span>
                                            <?= Html::hiddenInput('item_statuses[' . $item->id . ']', 'approved') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            $itemStatusClass = [
                                                StoreTransferItem::STATUS_PENDING => 'label-warning',
                                                StoreTransferItem::STATUS_APPROVED => 'label-success',
                                                StoreTransferItem::STATUS_REJECTED => 'label-danger',
                                                StoreTransferItem::STATUS_TRANSFERRED => 'label-info',
                                            ];
                                        ?>
                                        <span class="label <?= $itemStatusClass[$item->item_status] ?? 'label-default' ?>">
                                            <?= $item->getStatusLabel() ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <div class="form-group">
                    <?= Html::submitButton(
                        '<span class="glyphicon glyphicon-ok"></span> Утвердить и завершить заявку',
                        ['class' => 'btn btn-success btn-fill btn-lg']
                    ) ?>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-arrow-left"></span> Назад к просмотру',
                        ['view', 'id' => $model->id],
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
        <h4 class="title">Инструкция для администратора</h4>
    </div>
    <div class="content">
        <ul>
            <li><strong>Передано</strong> - количество, которое филиал-источник указал для передачи</li>
            <li><strong>Утвердить количество</strong> - окончательное количество, которое будет учтено в системе</li>
            <li>Используйте кнопку <span class="glyphicon glyphicon-arrow-left"></span> для копирования переданного количества</li>
            <li>Используйте кнопку <span class="glyphicon glyphicon-arrow-up"></span> для копирования запрошенного количества</li>
            <li>В выпадающем списке "Решение" выберите действие для каждой позиции:
                <ul>
                    <li><strong>Утвердить</strong> - подтвердить передачу товара</li>
                    <li><strong>Отклонить</strong> - отказать в передаче (установит количество в 0)</li>
                </ul>
            </li>
            <li>Используйте массовые действия для быстрой обработки всех позиций</li>
            <li>После нажатия "Утвердить и завершить заявку" заявка получит окончательный статус</li>
        </ul>
    </div>
</div>
