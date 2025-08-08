<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\ProductTimeLimitation */
/* @var $form yii\widgets\ActiveForm */
/* @var $products array */

// Prepare URLs for JavaScript
$getProductNameUrl = Url::to(['get-product-name']);
$searchProductsUrl = Url::to(['search-products']);
?>

<div class="product-time-limitation-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group field-producttimeimitation-productid required">
        <label class="control-label" for="producttimeimitation-productid">Продукт</label>
        <div class="input-group">
            <input type="hidden" id="producttimeimitation-productid" class="form-control" name="ProductTimeLimitation[productId]" value="<?= $model->productId ?>">
            <input type="text" id="product-search" class="form-control" placeholder="Начните вводить название продукта...">
            <span class="input-group-addon"><i class="fa fa-search"></i></span>
        </div>
        <div class="help-block"></div>
    </div>

    <div id="product-list" class="list-group" style="position: relative; z-index: 1000; display: none; max-height: 300px; overflow-y: auto; width: 100%; margin-top: -1px; border: 1px solid #ccc; border-radius: 0 0 4px 4px;"></div>

    <div class="row" style="margin-top: 15px;">
        <div class="col-md-6">
            <?= $form->field($model, 'startTime')->textInput([
                'type' => 'text', 
                'class' => 'form-control time-input',
                'placeholder' => 'ЧЧ:ММ (24-часовой формат)',
                'pattern' => '([01]?[0-9]|2[0-3]):[0-5][0-9]',
                'maxlength' => '5'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'endTime')->textInput([
                'type' => 'text', 
                'class' => 'form-control time-input',
                'placeholder' => 'ЧЧ:ММ (24-часовой формат)',
                'pattern' => '([01]?[0-9]|2[0-3]):[0-5][0-9]',
                'maxlength' => '5'
            ]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success btn-fill']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default btn-fill']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$css = <<<CSS
#product-list {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
#product-list a {
    border-left: none;
    border-right: none;
    border-radius: 0;
}
#product-list a:first-child {
    border-top: none;
}
#product-list a:hover {
    background-color: #f5f5f5;
    cursor: pointer;
}
.selected-product {
    font-weight: bold;
    background-color: #f9f9f9;
}
CSS;

$this->registerCss($css);

$js = <<<JS
$(document).ready(function() {
    // Time input validation and formatting
    $('.time-input').on('input', function(e) {
        let input = this;
        let value = input.value;
        let selectionStart = input.selectionStart;
        
        // Only format if we're adding text, not deleting
        if (value.length >= $(this).data('prev-length') || !$(this).data('prev-length')) {
            // Auto-add colon after 2 digits if not present
            if (value.length === 2 && !value.includes(':')) {
                value += ':';
                selectionStart++;
            }
            
            // Validate time format
            if (value.length > 0) {
                // Split by colon and validate parts
                const parts = value.split(':');
                
                // Validate hours (0-23)
                if (parts[0] && parts[0].length > 0) {
                    const hours = parseInt(parts[0]);
                    if (!isNaN(hours) && hours > 23) {
                        parts[0] = '23';
                    }
                }
                
                // Validate minutes (0-59)
                if (parts.length > 1 && parts[1] && parts[1].length > 0) {
                    const minutes = parseInt(parts[1]);
                    if (!isNaN(minutes) && minutes > 59) {
                        parts[1] = '59';
                    }
                }
                
                // Reconstruct value only if we need to change it
                const newValue = parts.length > 1 ? parts.join(':') : parts[0];
                if (newValue !== value) {
                    input.value = newValue;
                    input.setSelectionRange(selectionStart, selectionStart);
                }
            }
        }
        
        // Store the current length for next time
        $(this).data('prev-length', input.value.length);
    });
    
    // Allow backspace and delete to work normally
    $('.time-input').on('keydown', function(e) {
        // Store selection position before keydown
        $(this).data('selection', this.selectionStart);
        
        // Allow: backspace, delete, tab, escape, enter
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
            // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
            // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40) ||
            // Allow colon
            e.keyCode === 186 || e.keyCode === 59) {
            return;
        }
        
        // Ensure that it is a number and stop the keypress if not
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && 
            (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    
    var productList = $('#product-list');
    var productSearch = $('#product-search');
    var productIdField = $('#producttimeimitation-productid');
    
    // Set initial product name if editing
    if (productIdField.val()) {
        $.ajax({
            url: '{$getProductNameUrl}',
            type: 'GET',
            data: { id: productIdField.val() },
            success: function(response) {
                if (response.success) {
                    productSearch.val(response.name);
                    productSearch.addClass('selected-product');
                }
            }
        });
    }
    
    productSearch.on('input', function() {
        var searchTerm = $(this).val();
        
        productSearch.removeClass('selected-product');
        
        if (searchTerm.length < 2) {
            productList.hide();
            return;
        }
        
        $.ajax({
            url: '{$searchProductsUrl}',
            type: 'GET',
            data: { term: searchTerm },
            success: function(response) {
                productList.empty();
                
                if (response.length > 0) {
                    $.each(response, function(index, product) {
                        var item = $('<a href="#" class="list-group-item"></a>')
                            .text(product.name)
                            .data('id', product.id)
                            .on('click', function(e) {
                                e.preventDefault();
                                productIdField.val($(this).data('id'));
                                productSearch.val($(this).text());
                                productSearch.addClass('selected-product');
                                productList.hide();
                            });
                        productList.append(item);
                    });
                    
                    productList.show();
                } else {
                    productList.hide();
                }
            }
        });
    });
    
    // Hide product list when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#product-search, #product-list, .input-group-addon').length) {
            productList.hide();
        }
    });
    
    // Show product list when clicking on the search icon
    $('.input-group-addon').on('click', function() {
        if (productList.is(':hidden') && productSearch.val().length >= 2) {
            productSearch.trigger('input');
        }
    });
    
    // Keyboard navigation
    productSearch.on('keydown', function(e) {
        if (productList.is(':visible')) {
            var items = productList.find('a');
            var selected = productList.find('a.active');
            var index = items.index(selected);
            
            // Down arrow
            if (e.keyCode === 40) {
                e.preventDefault();
                if (selected.length === 0) {
                    items.first().addClass('active');
                } else if (index < items.length - 1) {
                    selected.removeClass('active');
                    items.eq(index + 1).addClass('active');
                }
            }
            
            // Up arrow
            if (e.keyCode === 38) {
                e.preventDefault();
                if (selected.length === 0) {
                    items.last().addClass('active');
                } else if (index > 0) {
                    selected.removeClass('active');
                    items.eq(index - 1).addClass('active');
                }
            }
            
            // Enter
            if (e.keyCode === 13 && selected.length > 0) {
                e.preventDefault();
                selected.trigger('click');
            }
            
            // Escape
            if (e.keyCode === 27) {
                productList.hide();
            }
        }
    });
});
JS;

$this->registerJs($js);
?> 