<?php

use app\models\StoreTransfer;
use app\models\StoreTransferItem;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $transfers app\models\StoreTransfer[] */

$this->title = 'Входящие заявки на перемещение';
$this->params['breadcrumbs'][] = ['label' => 'Внутренние перемещения', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card">
    <div class="header">
        <h4 class="title">
            <?= Html::encode($this->title) ?>
            <div class="pull-right">
                <?= Html::a('Мои заявки', ['index'], ['class' => 'btn btn-default btn-fill btn-sm']) ?>
            </div>
        </h4>
        <p class="category">Заявки на передачу продуктов из вашего филиала</p>
    </div>
    <div class="content">
        <?php if (empty($transfers)): ?>
            <div class="alert alert-info">
                <strong>Нет входящих заявок</strong><br>
                У вас нет активных заявок на передачу продуктов.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Филиал-получатель</th>
                            <th>Количество позиций</th>
                            <th>Дата создания</th>
                            <th>Статус</th>
                            <th style="width: 150px;" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transfers as $transfer): ?>
                            <?php
                                $currentStoreId = Yii::$app->user->identity->store_id;
                                $itemsForStore = StoreTransferItem::find()
                                    ->where([
                                        'transfer_id' => $transfer->id,
                                        'source_store_id' => $currentStoreId,
                                    ])
                                    ->all();

                                $pendingCount = 0;
                                $processedCount = 0;
                                foreach ($itemsForStore as $item) {
                                    if ($item->item_status === StoreTransferItem::STATUS_PENDING) {
                                        $pendingCount++;
                                    } else {
                                        $processedCount++;
                                    }
                                }
                                $totalCount = count($itemsForStore);
                            ?>
                            <tr>
                                <td><?= $transfer->id ?></td>
                                <td>
                                    <strong><?= Html::encode($transfer->requestStore->name) ?></strong>
                                    <?php if ($transfer->comment): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="glyphicon glyphicon-comment"></i>
                                            <?= Html::encode($transfer->comment) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $totalCount ?> шт.
                                    <?php if ($processedCount > 0): ?>
                                        <br>
                                        <small class="text-success">Обработано: <?= $processedCount ?></small>
                                    <?php endif; ?>
                                    <?php if ($pendingCount > 0): ?>
                                        <br>
                                        <small class="text-warning">Ожидает: <?= $pendingCount ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= Yii::$app->formatter->asDatetime($transfer->created_at, 'php:d.m.Y H:i') ?></td>
                                <td>
                                    <?php
                                        $statusClass = [
                                            StoreTransfer::STATUS_NEW => 'label-primary',
                                            StoreTransfer::STATUS_IN_PROGRESS => 'label-warning',
                                            StoreTransfer::STATUS_COMPLETED => 'label-success',
                                            StoreTransfer::STATUS_CANCELLED => 'label-default',
                                        ];
                                    ?>
                                    <span class="label <?= $statusClass[$transfer->status] ?? 'label-default' ?>">
                                        <?= $transfer->getStatusLabel() ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($pendingCount > 0): ?>
                                        <?= Html::a(
                                            '<span class="glyphicon glyphicon-ok"></span> Обработать',
                                            ['process-incoming', 'id' => $transfer->id],
                                            ['class' => 'btn btn-success btn-sm']
                                        ) ?>
                                    <?php else: ?>
                                        <?= Html::a(
                                            '<span class="glyphicon glyphicon-eye-open"></span> Просмотр',
                                            ['view', 'id' => $transfer->id],
                                            ['class' => 'btn btn-info btn-sm']
                                        ) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
