<?php

use app\models\Products;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProductWriteoff */
/* @var $folders array */

$this->title = "Списание: " . $model->store->name;
$this->params['breadcrumbs'][] = ['label' => 'Списания', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

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

$css = <<<CSS
.product-search-container {
    position: relative;
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
    margin-top: 20px;
}
.selected-products-table .remove-btn {
    cursor: pointer;
    color: #d9534f;
}
.selected-products-table .remove-btn:hover {
    color: #c9302c;
}
.photo-upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}
.photo-upload-area:hover {
    border-color: #5cb85c;
    background: #f0f9f0;
}
.photo-upload-area.dragover {
    border-color: #5cb85c;
    background: #e8f5e8;
    transform: scale(1.02);
}
.photo-previews {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
}
.photo-preview-item {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.photo-preview-item:hover {
    transform: scale(1.05);
}
.photo-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-preview-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 28px;
    height: 28px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    line-height: 1;
    transition: background 0.2s;
}
.photo-preview-remove:hover {
    background: rgba(200, 35, 51, 1);
}
CSS;

$this->registerCss($css);

$js = <<<JS
var allProducts = $productsJson;
var selectedProducts = {};
var rowCounter = 0;

// Поиск продуктов
$('#product-search').on('input', function() {
    var searchTerm = $(this).val().toLowerCase();

    if (searchTerm.length < 2) {
        $('.product-suggestions').hide();
        return;
    }

    var matches = allProducts.filter(function(product) {
        return product.name.toLowerCase().indexOf(searchTerm) !== -1;
    });

    if (matches.length > 0) {
        var html = '';
        matches.slice(0, 10).forEach(function(product) {
            html += '<div class="product-suggestion-item" data-id="' + product.id + '" data-name="' + product.name + '" data-unit="' + product.unit + '">';
            html += '<div class="product-name">' + product.name + ' (' + product.unit + ')</div>';
            html += '<div class="product-category">' + product.category + '</div>';
            html += '</div>';
        });
        $('.product-suggestions').html(html).show();
    } else {
        $('.product-suggestions').hide();
    }
});

// Выбор продукта из списка
$(document).on('click', '.product-suggestion-item', function() {
    var productId = $(this).data('id');
    var productName = $(this).data('name');
    var productUnit = $(this).data('unit');

    addProductRow(productId, productName, productUnit);

    $('#product-search').val('');
    $('.product-suggestions').hide();
});

// Закрытие списка при клике вне его
$(document).on('click', function(e) {
    if (!$(e.target).closest('.product-search-container').length) {
        $('.product-suggestions').hide();
    }
});

// Добавление строки продукта
function addProductRow(productId, productName, productUnit) {
    if (selectedProducts[productId]) {
        // Продукт уже добавлен, фокусируемся на его поле
        $('#row-' + productId + ' .quantity-input').focus();
        return;
    }

    selectedProducts[productId] = true;

    var html = '<tr id="row-' + productId + '">';
    html += '<td>' + productName + '</td>';
    html += '<td class="text-center">' + productUnit + '</td>';
    html += '<td width="200"><input type="number" class="form-control quantity-input" name="items[' + productId + ']" value="" step="any" min="0.01" /></td>';
    html += '<td width="50" class="text-center"><span class="glyphicon glyphicon-trash remove-btn" data-id="' + productId + '"></span></td>';
    html += '</tr>';

    $('#selected-products-body').append(html);
    $('#selected-products-table').show();
    $('#no-products-message').hide();

    // Автофокус на поле количества
    $('#row-' + productId + ' .quantity-input').focus();
}

// Удаление строки продукта
$(document).on('click', '.remove-btn', function() {
    var productId = $(this).data('id');
    delete selectedProducts[productId];
    $('#row-' + productId).remove();

    if ($('#selected-products-body tr').length === 0) {
        $('#selected-products-table').hide();
        $('#no-products-message').show();
    }
});

// Валидация формы
$('#writeoff-form').on('submit', function(e) {
    console.log('Form submit triggered');

    var hasProducts = $('#selected-products-body tr').length > 0;
    console.log('Has products:', hasProducts);

    if (!hasProducts) {
        alert('Добавьте хотя бы один продукт для списания');
        e.preventDefault();
        return false;
    }

    var hasValidQuantity = false;
    var formData = {};
    $('.quantity-input').each(function() {
        var val = $(this).val();
        var name = $(this).attr('name');
        console.log('Input:', name, '=', val);
        formData[name] = val;

        if (val && parseFloat(val) > 0) {
            hasValidQuantity = true;
        }
    });

    console.log('Form data:', formData);
    console.log('Has valid quantity:', hasValidQuantity);

    if (!hasValidQuantity) {
        alert('Укажите количество для списания');
        e.preventDefault();
        return false;
    }

    console.log('Form will be submitted');
});

// === Загрузка фотографий ===
var photoFiles = [];
var photoInput = document.getElementById('photo-input');
var uploadArea = document.getElementById('photo-upload-area');
var previewsContainer = document.getElementById('photo-previews');

// Клик по области загрузки
uploadArea.addEventListener('click', function() {
    photoInput.click();
});

// Обработка выбора файлов
photoInput.addEventListener('change', function(e) {
    handleFiles(e.target.files);
});

// Drag & Drop
uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.remove('dragover');

    var files = e.dataTransfer.files;
    handleFiles(files);
});

// Обработка файлов
function handleFiles(files) {
    var dt = new DataTransfer();

    // Добавляем старые файлы
    for (var i = 0; i < photoFiles.length; i++) {
        dt.items.add(photoFiles[i]);
    }

    // Добавляем новые файлы
    for (var i = 0; i < files.length; i++) {
        if (files[i].type.startsWith('image/')) {
            dt.items.add(files[i]);
            photoFiles.push(files[i]);
        }
    }

    photoInput.files = dt.files;
    updatePreviews();
}

// Обновление превью
function updatePreviews() {
    previewsContainer.innerHTML = '';

    for (var i = 0; i < photoFiles.length; i++) {
        (function(index, file) {
            var reader = new FileReader();

            reader.onload = function(e) {
                var div = document.createElement('div');
                div.className = 'photo-preview-item';
                div.innerHTML = '<img src="' + e.target.result + '">' +
                    '<button type="button" class="photo-preview-remove" data-index="' + index + '">&times;</button>';
                previewsContainer.appendChild(div);
            };

            reader.readAsDataURL(file);
        })(i, photoFiles[i]);
    }
}

// Удаление фото
previewsContainer.addEventListener('click', function(e) {
    if (e.target.classList.contains('photo-preview-remove') ||
        e.target.parentElement.classList.contains('photo-preview-remove')) {

        var button = e.target.classList.contains('photo-preview-remove') ?
            e.target : e.target.parentElement;
        var index = parseInt(button.getAttribute('data-index'));

        // Удаляем файл из массива
        photoFiles.splice(index, 1);

        // Обновляем input
        var dt = new DataTransfer();
        for (var i = 0; i < photoFiles.length; i++) {
            dt.items.add(photoFiles[i]);
        }
        photoInput.files = dt.files;

        // Обновляем превью
        updatePreviews();
    }
});

// Фокус на поле поиска при загрузке
$(document).ready(function() {
    $('#product-search').focus();
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
            'id' => 'writeoff-form',
            'options' => ['enctype' => 'multipart/form-data'],
        ]); ?>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label>Комментарий (необязательно)</label>
                    <textarea class="form-control" name="comment" rows="3" placeholder="Добавьте комментарий к списанию..."></textarea>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label>Поиск продукта</label>
                    <div class="product-search-container">
                        <input
                            type="text"
                            class="form-control"
                            id="product-search"
                            placeholder="Начните вводить название продукта..."
                            autocomplete="off">
                        <div class="product-suggestions"></div>
                    </div>
                    <p class="help-block">Введите минимум 2 символа для поиска</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div id="no-products-message" class="alert alert-info">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    Добавьте продукты для списания, используя поиск выше
                </div>

                <div id="selected-products-table" class="selected-products-table" style="display: none;">
                    <h5><strong>Выбранные продукты:</strong></h5>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Наименование</th>
                                <th class="text-center" width="120">Ед. изм.</th>
                                <th width="200">Количество</th>
                                <th class="text-center" width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="selected-products-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label>Фотографии (необязательно)</label>
                    <div class="photo-upload-container">
                        <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" style="display: none;">
                        <div class="photo-upload-area" id="photo-upload-area">
                            <i class="glyphicon glyphicon-camera" style="font-size: 48px; color: #ccc;"></i>
                            <p style="margin-top: 15px; color: #999;">Нажмите или перетащите фотографии сюда</p>
                            <p style="color: #bbb; font-size: 12px;">Можно выбрать несколько файлов</p>
                        </div>
                        <div id="photo-previews" class="photo-previews"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="form-group">
                    <?= Html::submitButton('Создать списания', ['class' => 'btn btn-success btn-fill btn-lg']) ?>
                    <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default btn-lg']) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
