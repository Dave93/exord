<?php

use yii\helpers\Html;
use app\models\OrderItemsChangelog;

/* @var $this yii\web\View */
/* @var $changelog app\models\OrderItemsChangelog[] */

?>

<?php if (empty($changelog)): ?>
    <div class="alert alert-info">
        История изменений пуста
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th style="width: 140px;">Дата и время</th>
                    <th>Продукт</th>
                    <th style="width: 120px;">Действие</th>
                    <th style="width: 100px;">Старое кол-во</th>
                    <th style="width: 100px;">Новое кол-во</th>
                    <th style="width: 120px;">Пользователь</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($changelog as $change): ?>
                    <?php
                    // Определяем класс бейджа в зависимости от действия
                    $badgeClass = '';
                    switch ($change->action) {
                        case OrderItemsChangelog::ACTION_ADDED:
                            $badgeClass = 'badge-success';
                            break;
                        case OrderItemsChangelog::ACTION_DELETED:
                            $badgeClass = 'badge-danger';
                            break;
                        case OrderItemsChangelog::ACTION_UPDATED:
                            $badgeClass = 'badge-warning';
                            break;
                        case OrderItemsChangelog::ACTION_RESTORED:
                            $badgeClass = 'badge-info';
                            break;
                    }
                    ?>
                    <tr>
                        <td><?= Yii::$app->formatter->asDatetime($change->created_at, 'php:d.m.Y H:i:s') ?></td>
                        <td><?= Html::encode($change->product->name ?? 'Продукт удален') ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <?= $change->getActionLabel() ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?= $change->old_quantity !== null ? $change->old_quantity : '-' ?>
                        </td>
                        <td class="text-center">
                            <?= $change->new_quantity !== null ? $change->new_quantity : '-' ?>
                        </td>
                        <td><?= Html::encode($change->user->username ?? 'Неизвестно') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
        .badge {
            display: inline-block;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 3px;
        }
        .badge-success {
            background-color: #5cb85c;
        }
        .badge-danger {
            background-color: #d9534f;
        }
        .badge-warning {
            background-color: #f0ad4e;
        }
        .badge-info {
            background-color: #5bc0de;
        }
    </style>
<?php endif; ?>
