<?php

use app\models\Products;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\StoreTransfer */
/* @var $stores app\models\Stores[] */
/* @var $folders array */

$this->title = 'Редактирование заявки #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Внутренние перемещения', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Заявка #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';

// Собираем все продукты для автокомплита
$allProducts = [];
foreach ($folders as $folder) {
    $items = Products::getProducts($folder['id'], 0, Yii::$app->user->id, false);
    foreach ($items as $item) {
        $allProducts[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'unit' => $item['mainUnit'],
            'category' => $folder['name'],
        ];
    }
}
$productsJson = Json::encode($allProducts);

// Формируем список филиалов
$storesData = [];
foreach ($stores as $store) {
    $storesData[] = [
        'id' => $store->id,
        'name' => $store->name,
    ];
}
$storesJson = Json::encode($storesData);

// Собираем уже существующие данные заявки
$existingData = [];
foreach ($model->items as $item) {
    if (!isset($existingData[$item->source_store_id])) {
        $existingData[$item->source_store_id] = [
            'store_name' => $item->sourceStore->name,
            'products' => [],
        ];
    }
    $existingData[$item->source_store_id]['products'][] = [
        'id' => $item->product_id,
        'name' => $item->product->name,
        'unit' => $item->product->mainUnit,
        'quantity' => $item->requested_quantity,
    ];
}
$existingDataJson = Json::encode($existingData);

// Используем тот же CSS и JS что и в create.php, но с адаптацией для загрузки существующих данных
$css = file_get_contents(__DIR__ . '/create.php');
preg_match('/\$css = <<<CSS(.*?)CSS;/s', $css, $matches);
$cssContent = $matches[1] ?? '';
$this->registerCss($cssContent);

$js = <<<JS
var allProducts = $productsJson;
var availableStores = $storesJson;
var existingData = $existingDataJson;
var storeCounter = 0;
var storeData = {};

// Загрузка существующих данных
$(document).ready(function() {
    for (var storeId in existingData) {
        var storeInfo = existingData[storeId];
        addStoreSection(storeId, storeInfo.store_name);

        storeInfo.products.forEach(function(product) {
            addProductRow(storeId, product.id, product.name, product.unit, product.quantity);
        });
    }
});

// Добавление нового филиала-источника
$('#add-store-btn').click(function() {
    var storeId = $('#store-select').val();
    var storeName = $('#store-select option:selected').text();

    if (!storeId) {
        alert('Выберите филиал');
        return;
    }

    if (storeData[storeId]) {
        alert('Этот филиал уже добавлен');
        return;
    }

    addStoreSection(storeId, storeName);
});

function addStoreSection(storeId, storeName) {
    storeData[storeId] = { products: {} };

    var html = '<div class="store-section" id="store-section-' + storeId + '">';
    html += '<div class="store-section-header">';
    html += '<div class="store-section-title">' + storeName + '</div>';
    html += '<span class="glyphicon glyphicon-trash remove-store-btn" data-store-id="' + storeId + '"></span>';
    html += '</div>';

    html += '<div class="product-search-container">';
    html += '<input type="text" class="form-control product-search" placeholder="Начните вводить название продукта..." autocomplete="off" data-store-id="' + storeId + '">';
    html += '<div class="product-suggestions"></div>';
    html += '</div>';

    html += '<div class="no-products-message" style="padding: 10px; background: #fff; border-radius: 4px; color: #999;">Добавьте продукты для перемещения</div>';

    html += '<div class="selected-products-table" style="display: none;">';
    html += '<table class="table table-bordered table-sm">';
    html += '<thead><tr><th>Наименование</th><th class="text-center" width="120">Ед. изм.</th><th width="150">Количество</th><th class="text-center" width="50"></th></tr></thead>';
    html += '<tbody class="products-tbody"></tbody>';
    html += '</table>';
    html += '</div>';

    html += '</div>';

    $('#stores-container').append(html);
    $('#store-select').val('');
}

// Удаление филиала-источника
$(document).on('click', '.remove-store-btn', function() {
    var storeId = $(this).data('store-id');
    delete storeData[storeId];
    $('#store-section-' + storeId).remove();
});

// Поиск продуктов
$(document).on('input', '.product-search', function() {
    var storeId = $(this).data('store-id');
    var searchTerm = $(this).val().toLowerCase();
    var suggestionsContainer = $(this).siblings('.product-suggestions');

    if (searchTerm.length < 2) {
        suggestionsContainer.hide();
        return;
    }

    var matches = allProducts.filter(function(product) {
        return product.name.toLowerCase().indexOf(searchTerm) !== -1;
    });

    if (matches.length > 0) {
        var html = '';
        matches.slice(0, 10).forEach(function(product) {
            html += '<div class="product-suggestion-item" data-store-id="' + storeId + '" data-id="' + product.id + '" data-name="' + product.name + '" data-unit="' + product.unit + '">';
            html += '<div class="product-name">' + product.name + ' (' + product.unit + ')</div>';
            html += '<div class="product-category">' + product.category + '</div>';
            html += '</div>';
        });
        suggestionsContainer.html(html).show();
    } else {
        suggestionsContainer.hide();
    }
});

// Выбор продукта из списка
$(document).on('click', '.product-suggestion-item', function() {
    var storeId = $(this).data('store-id');
    var productId = $(this).data('id');
    var productName = $(this).data('name');
    var productUnit = $(this).data('unit');

    addProductRow(storeId, productId, productName, productUnit);

    $('#store-section-' + storeId + ' .product-search').val('');
    $('.product-suggestions').hide();
});

// Закрытие списка при клике вне его
$(document).on('click', function(e) {
    if (!$(e.target).closest('.product-search-container').length) {
        $('.product-suggestions').hide();
    }
});

// Добавление строки продукта
function addProductRow(storeId, productId, productName, productUnit, quantity) {
    quantity = quantity || '';

    if (storeData[storeId].products[productId]) {
        // Продукт уже добавлен, фокусируемся на его поле
        $('#store-section-' + storeId + ' #product-row-' + productId + ' .quantity-input').focus();
        return;
    }

    storeData[storeId].products[productId] = true;

    var html = '<tr id="product-row-' + productId + '">';
    html += '<td>' + productName + '</td>';
    html += '<td class="text-center">' + productUnit + '</td>';
    html += '<td><input type="number" class="form-control quantity-input" name="transfers[' + storeId + '][' + productId + ']" value="' + quantity + '" step="any" min="0.01" /></td>';
    html += '<td class="text-center"><span class="glyphicon glyphicon-trash remove-product-btn" data-store-id="' + storeId + '" data-product-id="' + productId + '"></span></td>';
    html += '</tr>';

    var storeSection = $('#store-section-' + storeId);
    storeSection.find('.products-tbody').append(html);
    storeSection.find('.selected-products-table').show();
    storeSection.find('.no-products-message').hide();

    if (!quantity) {
        $('#product-row-' + productId + ' .quantity-input').focus();
    }
}

// Удаление строки продукта
$(document).on('click', '.remove-product-btn', function() {
    var storeId = $(this).data('store-id');
    var productId = $(this).data('product-id');

    delete storeData[storeId].products[productId];
    $('#product-row-' + productId).remove();

    var storeSection = $('#store-section-' + storeId);
    if (storeSection.find('.products-tbody tr').length === 0) {
        storeSection.find('.selected-products-table').hide();
        storeSection.find('.no-products-message').show();
    }
});

// Валидация формы
$('#transfer-form').on('submit', function(e) {
    if (Object.keys(storeData).length === 0) {
        alert('Добавьте хотя бы один филиал-источник');
        e.preventDefault();
        return false;
    }

    var hasProducts = false;
    var hasValidQuantity = false;

    $('.quantity-input').each(function() {
        hasProducts = true;
        var val = $(this).val();
        if (val && parseFloat(val) > 0) {
            hasValidQuantity = true;
        }
    });

    if (!hasProducts) {
        alert('Добавьте хотя бы один продукт для перемещения');
        e.preventDefault();
        return false;
    }

    if (!hasValidQuantity) {
        alert('Укажите количество для перемещения');
        e.preventDefault();
        return false;
    }
});
JS;

$this->registerJs($js);
?>

<div class="card">
    <div class="header">
        <h4 class="title"><?= Html::encode($this->title) ?></h4>
    </div>
    <div class="content">
        <?php $form = ActiveForm::begin([
            'id' => 'transfer-form',
        ]); ?>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label>Комментарий (необязательно)</label>
                    <textarea class="form-control" name="comment" rows="3" placeholder="Добавьте комментарий к заявке..."><?= Html::encode($model->comment) ?></textarea>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="form-group">
                    <label>Добавить филиал-источник</label>
                    <div class="input-group">
                        <select class="form-control" id="store-select">
                            <option value="">Выберите филиал</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= $store->id ?>"><?= Html::encode($store->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary" id="add-store-btn">
                                <i class="glyphicon glyphicon-plus"></i> Добавить филиал
                            </button>
                        </span>
                    </div>
                    <p class="help-block">Выберите филиал, с которого хотите получить продукты</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div id="stores-container"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="form-group">
                    <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-success btn-fill btn-lg']) ?>
                    <?= Html::a('Отмена', ['view', 'id' => $model->id], ['class' => 'btn btn-default btn-lg']) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<style>
.store-section {
    border: 2px solid #e3e3e3;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    background: #f9f9f9;
}
.store-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.store-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}
.remove-store-btn {
    cursor: pointer;
    color: #d9534f;
    font-size: 20px;
}
.remove-store-btn:hover {
    color: #c9302c;
}
.product-search-container {
    position: relative;
    margin-bottom: 10px;
}
.product-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.product-suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.product-suggestion-item:hover {
    background-color: #f5f5f5;
}
.product-suggestion-item .product-name {
    font-weight: 600;
    color: #333;
}
.product-suggestion-item .product-category {
    font-size: 12px;
    color: #999;
    margin-top: 2px;
}
.selected-products-table {
    margin-top: 15px;
}
.selected-products-table .remove-btn,
.remove-product-btn {
    cursor: pointer;
    color: #d9534f;
}
.selected-products-table .remove-btn:hover,
.remove-product-btn:hover {
    color: #c9302c;
}
.add-store-btn {
    margin-bottom: 20px;
}
</style>
