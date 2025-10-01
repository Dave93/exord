<?php

use app\models\Products;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProductWriteoff */
/* @var $folders array */

$this->title = "Списание: " . $model->store->name;
$this->params['breadcrumbs'][] = ['label' => 'Списания', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$list = "";

foreach ($folders as $folder) {
    $itemList = "";
    $items = Products::getProducts($folder['id'], 0, Yii::$app->user->id, false);

    foreach ($items as $item) {
        $itemList .= <<<HTML
        <tr id="p-{$item['id']}">
            <td>{$item['name']}</td>
            <td width="100" class="text-center">{$item['mainUnit']}</td>
            <td width="200">
                <input type="number" class="form-control quantity" name="items[{$item['id']}]" value="" step="any" min="0" />
            </td>
        </tr>
HTML;
    }

    if (empty($itemList))
        continue;

    $list .= <<<LIST
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" href="#collapse-{$folder['id']}">{$folder['name']}</a>
            </h4>
        </div>
        <div id="collapse-{$folder['id']}" class="panel-collapse collapse">
            <table class="table table-hover table-striped order-table">
                <thead>
                <tr>
                    <th>Наименование</th>
                    <th class="text-center">Ед. изм.</th>
                    <th>Количество</th>
                </tr>
                </thead>
                <tbody>
                    {$itemList}
                </tbody>
            </table>
        </div>
    </div>
LIST;
}

$js = <<<JS
  $("#searchField").on("keyup", function() {
    let value = $(this).val().toLowerCase();
    $(".order-table tbody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });

  // Раскрываем все категории при поиске
  $("#searchField").on("keyup", function() {
    if ($(this).val().length > 0) {
      $(".panel-collapse").addClass("in");
    } else {
      $(".panel-collapse").removeClass("in");
    }
  });

  // Валидация формы перед отправкой
  $("#writeoff-form").on("submit", function(e) {
    let hasItems = false;
    $(".quantity").each(function() {
      if ($(this).val() > 0) {
        hasItems = true;
        return false;
      }
    });

    if (!hasItems) {
      alert("Необходимо указать хотя бы один продукт для списания");
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
        <?php $form = ActiveForm::begin(['id' => 'writeoff-form', 'options' => ['class' => 'form-horizontal']]); ?>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="col-md-2 control-label">Поиск продукта</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="searchField" placeholder="Введите название продукта...">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel-group">
                    <?= $list ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="col-md-offset-2 col-md-10">
                        <?= Html::submitButton('Создать списания', ['class' => 'btn btn-success btn-fill']) ?>
                        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
