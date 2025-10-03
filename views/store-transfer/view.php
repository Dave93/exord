<?php

use app\models\StoreTransfer;
use app\models\StoreTransferItem;
use app\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\StoreTransfer */
/* @var $itemsByStore array */

$this->title = 'Заявка на перемещение #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Внутренние перемещения', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$isAdmin = in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE]);
?>

<div class="card">
    <div class="header">
        <h4 class="title">
            <?= Html::encode($this->title) ?>
            <span class="pull-right">
                <?php
                $statusClass = [
                    StoreTransfer::STATUS_NEW => 'label-primary',
                    StoreTransfer::STATUS_IN_PROGRESS => 'label-warning',
                    StoreTransfer::STATUS_COMPLETED => 'label-success',
                    StoreTransfer::STATUS_CANCELLED => 'label-default',
                ];
                ?>
                <span class="label <?= $statusClass[$model->status] ?? 'label-default' ?>" style="font-size: 14px;">
                    <?= $model->getStatusLabel() ?>
                </span>
            </span>
        </h4>
    </div>
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'id',
                        [
                            'attribute' => 'request_store_id',
                            'label' => 'Филиал-заказчик',
                            'value' => $model->requestStore->name,
                        ],
                        [
                            'attribute' => 'created_by',
                            'label' => 'Создал',
                            'value' => $model->createdBy->fullname,
                        ],
                        [
                            'attribute' => 'created_at',
                            'format' => ['date', 'php:d.m.Y H:i'],
                        ],
                        'comment:ntext',
                    ],
                ]) ?>
            </div>
        </div>

        <?php if ($model->comment): ?>
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <strong>Комментарий:</strong><br>
                    <?= nl2br(Html::encode($model->comment)) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Позиции заявки по филиалам -->
        <div class="row" style="margin-top: 30px;">
            <div class="col-md-12">
                <h5><strong>Запрошенные продукты:</strong></h5>

                <?php foreach ($itemsByStore as $storeId => $data): ?>
                    <div style="border: 2px solid #e3e3e3; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f9f9f9;">
                        <h6 style="font-size: 16px; font-weight: 600; margin-bottom: 15px;">
                            <?= Html::encode($data['store']->name) ?>
                        </h6>

                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Продукт</th>
                                    <th class="text-center" width="120">Ед. изм.</th>
                                    <th class="text-center" width="150">Запрошено</th>
                                    <th class="text-center" width="150">Утверждено</th>
                                    <th class="text-center" width="150">Передано</th>
                                    <th class="text-center" width="120">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['items'] as $item): ?>
                                    <tr>
                                        <td><?= Html::encode($item->product->name) ?></td>
                                        <td class="text-center"><?= Html::encode($item->product->mainUnit) ?></td>
                                        <td class="text-center"><?= $item->requested_quantity ?></td>
                                        <td class="text-center">
                                            <?= $item->approved_quantity !== null ? $item->approved_quantity : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $item->transferred_quantity !== null ? $item->transferred_quantity : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $itemStatusClass = [
                                                StoreTransferItem::STATUS_PENDING => 'label-default',
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
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Действия -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <?php if ($model->canEdit()): ?>
                    <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-fill']) ?>
                <?php endif; ?>

                <?php if ($model->canCancel()): ?>
                    <?= Html::a('Отменить заявку', ['cancel', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => 'Вы уверены, что хотите отменить эту заявку?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php endif; ?>

                <?php if ($isAdmin && $model->status === StoreTransfer::STATUS_NEW): ?>
                    <?= Html::a('Взять в работу', ['set-in-progress', 'id' => $model->id], [
                        'class' => 'btn btn-warning btn-fill',
                        'data' => ['method' => 'post'],
                    ]) ?>
                <?php endif; ?>

                <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>
</div>
