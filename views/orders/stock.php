<?php

use app\models\Orders;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $orders array */
/* @var $date string */
/* @var $orderId integer */

$this->title = "Заказы на: " . date("d.m.Y", strtotime($date));
$this->params['breadcrumbs'][] = $this->title;

$i = 0;
$list = "";
$content = "";
foreach ($orders as $order) {
    $active = "";
    $products = Orders::getOrderProducts($order['id']);
    if (empty($products))
        continue;

    if (($i == 0 && empty($orderId)) || $orderId == $order['id'])
        $active = "active";
    $color = '';
    if ($order['state'] == 1) {
        $color = 'bg-orange';
    }
    $d = date('d.m H:i', strtotime($order['addDate']));
    $list .= <<<HTML
        <li class="{$active} {$color}"><a href="#st-{$order['id']}" data-toggle="tab">{$order['name']} (#{$order['id']} - {$d})</a></li>
HTML;
    $content .= $this->render('_stock-tab', [
        'order' => $order,
        'products' => $products,
        'active' => $active
    ]);
    $i++;
}

// Подготовка данных для сводной таблицы
$branchOrders = [];
$allProductIds = [];
$productInfo = [];

// Группируем заказы по филиалам ($order['name'])
foreach ($orders as $order) {
    $tabProducts = Orders::getOrderProducts($order['id']);
    if (empty($tabProducts))
        continue;
    
    $branchName = $order['name'];
    
    if (!isset($branchOrders[$branchName])) {
        $branchOrders[$branchName] = [
            'name' => $branchName,
            'products' => []
        ];
    }
    // echo '<pre style="display: none;">'; print_r($tabProducts); echo '</pre>';
    // Собираем все продукты для этого филиала
    foreach ($tabProducts as $product) {
        if ($product['prepared'] == 1)
            continue;
        $productId = $product['productId'];
        $allProductIds[$productId] = $productId;
        
        // Сохраняем информацию о продукте
        if (!isset($productInfo[$productId])) {
            $productInfo[$productId] = [
                'name' => $product['name'],
                'unit' => $product['mainUnit'],
                'totalQuantity' => 0
            ];
        }
        
        // Добавляем количество к общему количеству продукта
        $productInfo[$productId]['totalQuantity'] += $product['quantity'];
        
        // Добавляем продукт в список продуктов для филиала
        if (!isset($branchOrders[$branchName]['products'][$productId])) {
            $branchOrders[$branchName]['products'][$productId] = $product['quantity'];
        } else {
            $branchOrders[$branchName]['products'][$productId] += $product['quantity'];
        }
    }
}

// Сортируем филиалы по алфавиту
ksort($branchOrders);

// Сортируем продукты по алфавиту по названию
uasort($productInfo, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Переупорядочиваем массив продуктов согласно сортировке
$sortedProductIds = array_keys($productInfo);

$js = <<<JS

    $(document).ready(function () {
        $(document).on('click', '.order-excel', function(e) {
            const el = $(this);
            e.preventDefault();
            e.stopPropagation();
            // console.log('davr');
            const form = $(this).closest('.tab-pane').find('form');
            // Get the form data
            var formData = form.serialize();
        
            // add isRedirect = 0 to form data
            formData += '&noRedirect=2';
            
            // Send the Ajax request
            $.ajax({
              url: $(this).attr('action'), // URL where the form data will be sent
              type: 'POST', // HTTP method (e.g., 'POST' or 'GET')
              data: formData, // Form data to be sent
              success: function(response) {
                // Handle the successful response
                window.location.href = el.attr('href');
              },
              error: function(xhr, status, error) {
                // Handle the error response
              }
            });
        });
        
        // Обработчик кнопки печати для сводной таблицы
        $(document).on('click', '.print-summary-table', function(e) {
            e.preventDefault();
            
            // Создаем новое окно для печати
            var printWindow = window.open('', '_blank', 'height=600,width=800');
            
            // Формируем содержимое для печати
            var printContent = '<html><head><title>Сводная таблица заказов на: ' + $(this).data('date') + '</title>';
            printContent += '<style>';
            printContent += 'body { font-family: Arial, sans-serif; }';
            printContent += 'table { width: 100%; border-collapse: collapse; }';
            printContent += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }';
            printContent += 'th { background-color: #f2f2f2; }';
            printContent += '.table-title { text-align: center; font-size: 18px; margin-bottom: 20px; }';
            printContent += '</style>';
            printContent += '</head><body>';
            
            // Добавляем заголовок
            printContent += '<div class="table-title">Сводная таблица заказов на: ' + $(this).data('date') + '</div>';
            
            // Клонируем таблицу
            var tableClone = $('#summary-table').clone();
            
            // Удаляем ненужную колонку с номерами (первая колонка)
            tableClone.find('tr').each(function() {
                $(this).find('th:first, td:first').remove();
            });
            
            // Добавляем таблицу в содержимое для печати
            printContent += tableClone.prop('outerHTML');
            printContent += '</body></html>';
            
            // Пишем в новое окно
            printWindow.document.open();
            printWindow.document.write(printContent);
            printWindow.document.close();
            
            // Запускаем печать после полной загрузки
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
                // printWindow.close(); // Раскомментируйте, чтобы автоматически закрывать окно после печати
            };
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
        <?php if (!empty($orders)): ?>
            <!-- Main view tabs -->
            <ul class="nav nav-tabs">
                <li class="active"><a href="#branch-view" data-toggle="tab">По филиалам</a></li>
                <li><a href="#product-view" data-toggle="tab">Сводная таблица</a></li>
            </ul>
            
            <div class="tab-content">
                <!-- Branch view tab -->
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
                                <h4 class="title" style="padding-bottom: 20px">Продукты</h4>
                                <div class="tab-content">
                                    <?= $content ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product view tab -->
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
                                    <th>№</th>
                                    <th>Филиал</th>
                                    <?php foreach ($sortedProductIds as $productId): ?>
                                        <th class="text-center">
                                            <?= $productInfo[$productId]['name'] ?>
                                            <br>
                                            <small>(<?= $productInfo[$productId]['unit'] ?>)</small>
                                            <br>
                                            <strong>Всего: <?= $productInfo[$productId]['totalQuantity'] ?></strong>
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
                                        <?php foreach ($sortedProductIds as $productId): ?>
                                            <td class="text-center">
                                                <?= isset($branch['products'][$productId]) ? $branch['products'][$productId] : '-' ?>
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
            <div class="alert alert-danger">На сегодня не найден заказы</div>
        <?php endif; ?>
    </div>
</div>
