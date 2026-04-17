<?php

use app\models\MealOrderItems;
use app\models\User;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $orders array */
/* @var $date string */
/* @var $orderId integer */

$this->title = "Заказы блюд на: " . date("d.m.Y", strtotime($date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";
foreach ($orders as $order) {
    $active = "";

    // Получаем позиции заказа
    $items = MealOrderItems::find()->where(['mealOrderId' => $order['id']])->all();
    if (empty($items))
        continue;

    if (($i == 0 && empty($orderId)) || $orderId == $order['id'])
        $active = "active";
    $color = '';
    if ($order['state'] == 1) {
        $color = 'bg-orange';
    } elseif ($order['state'] == 2) {
        $color = 'bg-green';
    }
    $d = date('d.m H:i', strtotime($order['addDate']));
    $userName = '';
    $user = \app\models\User::findOne($order['userId']);
    if ($user) {
        $userName = $user->username;
    }
    $list .= <<<HTML
        <li class="{$active} {$color}"><a href="#st-{$order['id']}" data-toggle="tab">{$order['name']} (#{$order['id']} - {$userName} - {$d})</a></li>
HTML;
    $content .= $this->render('_stock-tab', [
        'order' => $order,
        'items' => $items,
        'active' => $active
    ]);
    $i++;
}

// Подготовка данных для сводной таблицы
$branchOrders = [];
$allDishIds = [];
$dishInfo = [];

foreach ($orders as $order) {
    $tabItems = MealOrderItems::find()->where(['mealOrderId' => $order['id']])->all();
    if (empty($tabItems))
        continue;

    $branchName = $order['name'];

    if (!isset($branchOrders[$branchName])) {
        $branchOrders[$branchName] = [
            'name' => $branchName,
            'dishes' => []
        ];
    }

    foreach ($tabItems as $item) {
        $dishId = $item->dishId;
        $allDishIds[$dishId] = $dishId;

        if (!isset($dishInfo[$dishId])) {
            $dishInfo[$dishId] = [
                'name' => $item->dish ? $item->dish->name : '-',
                'unit' => $item->dish ? $item->dish->unit : '-',
                'totalQuantity' => 0
            ];
        }

        $dishInfo[$dishId]['totalQuantity'] += $item->quantity;

        if (!isset($branchOrders[$branchName]['dishes'][$dishId])) {
            $branchOrders[$branchName]['dishes'][$dishId] = $item->quantity;
        } else {
            $branchOrders[$branchName]['dishes'][$dishId] += $item->quantity;
        }
    }
}

ksort($branchOrders);

uasort($dishInfo, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});

$sortedDishIds = array_keys($dishInfo);

$js = <<<JS
    $(document).ready(function () {
        // Печать сводной таблицы
        $(document).on('click', '.print-summary-table', function(e) {
            e.preventDefault();
            var printWindow = window.open('', '_blank', 'height=600,width=800');
            var printContent = '<html><head><title>Сводная таблица заказов блюд на: ' + $(this).data('date') + '</title>';
            printContent += '<style>';
            printContent += 'body { font-family: Arial, sans-serif; }';
            printContent += 'table { width: 100%; border-collapse: collapse; }';
            printContent += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }';
            printContent += 'th { background-color: #f2f2f2; }';
            printContent += '.table-title { text-align: center; font-size: 18px; margin-bottom: 20px; }';
            printContent += '</style>';
            printContent += '</head><body>';
            printContent += '<div class="table-title">Сводная таблица заказов блюд на: ' + $(this).data('date') + '</div>';
            var tableClone = $('#summary-table').clone();
            tableClone.find('tr').each(function() {
                $(this).find('th:first, td:first').remove();
            });
            printContent += tableClone.prop('outerHTML');
            printContent += '</body></html>';
            printWindow.document.open();
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
            };
        });

        // Обработчик открытия модального окна с историей изменений
        $(document).on('click', '.show-changelog-modal', function(e) {
            e.preventDefault();
            var orderId = $(this).data('order-id');

            $('#changelog-modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Загрузка истории...</p></div>');

            $('#changelog-modal').modal('show');

            $.ajax({
                url: '/meal-orders/get-changelog',
                type: 'GET',
                data: { mealOrderId: orderId },
                success: function(response) {
                    $('#changelog-modal-body').html(response);
                },
                error: function() {
                    $('#changelog-modal-body').html('<div class="alert alert-danger">Ошибка при загрузке истории изменений</div>');
                }
            });
        });
    });
JS;
$this->registerJs($js);
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right"></p>
    </div>
    <hr>
    <div class="content table-responsive">
        <?php if (!empty($list)): ?>
            <ul class="nav nav-tabs">
                <li class="active"><a href="#branch-view" data-toggle="tab">По филиалам</a></li>
                <li><a href="#product-view" data-toggle="tab">Сводная таблица</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="branch-view">
                    <div class="orders-list">
                        <div class="row">
                            <div class="col-md-4 col-lg-3">
                                <h4 class="title" style="padding-left: 20px; padding-bottom: 20px;">Филиалы</h4>
                                <ul class="nav nav-tabs tabs-left">
                                    <?= $list ?>
                                </ul>
                            </div>

                            <div class="col-md-8 col-lg-9">
                                <h4 class="title" style="padding-bottom: 20px">Блюда</h4>
                                <div class="tab-content">
                                    <?= $content ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="product-view">
                    <div class="text-right" style="margin-bottom: 15px;">
                        <?= Html::a('<i class="glyphicon glyphicon-print"></i> Печать', '#', [
                            'class' => 'btn btn-primary btn-fill print-summary-table',
                            'data-date' => date("d.m.Y", strtotime($date))
                        ]) ?>
                    </div>
                    <div class="table-responsive">
                        <table id="summary-table" class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Филиал</th>
                                    <?php foreach ($sortedDishIds as $dishId): ?>
                                        <th class="text-center">
                                            <?= $dishInfo[$dishId]['name'] ?>
                                            <br>
                                            <small>(<?= $dishInfo[$dishId]['unit'] ?>)</small>
                                            <br>
                                            <strong>Всего: <?= $dishInfo[$dishId]['totalQuantity'] ?></strong>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($branchOrders as $branchName => $branch): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><strong><?= $branchName ?></strong></td>
                                        <?php foreach ($sortedDishIds as $dishId): ?>
                                            <td class="text-center">
                                                <?= isset($branch['dishes'][$dishId]) ? $branch['dishes'][$dishId] : '-' ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">На сегодня не найдены заказы блюд</div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для истории изменений -->
<div class="modal fade" id="changelog-modal" tabindex="-1" role="dialog" aria-labelledby="changelogModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="changelogModalLabel">История изменений заказа блюд</h4>
            </div>
            <div class="modal-body" id="changelog-modal-body">
                <!-- Содержимое будет загружено через AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
