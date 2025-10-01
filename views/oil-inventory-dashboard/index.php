<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\OilInventory;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $isManager bool */
/* @var $storeFilter string */
/* @var $dateFrom string */
/* @var $dateTo string */
/* @var $storesList array */
/* @var $totalRecords int */
/* @var $statusStats array */
/* @var $monthlyStats array */
/* @var $averageConsumption array */
/* @var $weeklyTrend array */
/* @var $topConsumptionDays array */
/* @var $recentRecords app\models\OilInventory[] */
/* @var $wastageAnalysis array */
/* @var $efficiencyMetrics array */
/* @var $unfilledRecords app\models\OilInventory[] */

$this->title = "Аналитика учета масла";
$this->params["breadcrumbs"][] = [
    "label" => "Учет масла",
    "url" => ["/oil-inventory/index"],
];
$this->params["breadcrumbs"][] = $this->title;

// Добавляем минимальные стили
$this->registerCss('
.oil-inventory-dashboard .well {
    margin-bottom: 20px;
}

.oil-inventory-dashboard .label {
    margin-left: 5px;
}

.alert-info .btn {
    transition: all 0.2s ease;
}

.alert-info .btn:hover {
    background-color: #f5f5f5 !important;
    color: #333 !important;
    border-color: #ccc !important;
}

.label-warning {
    background-color: #f39c12;
}

.label-success {
    background-color: #00a65a;
}

.label-danger {
    background-color: #dd4b39;
}

.label-info {
    background-color: #3c8dbc;
}
');

// Подготавливаем данные для графиков
$statusLabels = [];
$statusData = [];
$statusColors = [];

foreach ($statusStats as $stat) {
    $statusLabels[] =
        OilInventory::getStatusList()[$stat["status"]] ?? $stat["status"];
    $statusData[] = (int) $stat["count"];

    switch ($stat["status"]) {
        case OilInventory::STATUS_NEW:
            $statusColors[] = "#3c8dbc";
            break;
        case OilInventory::STATUS_FILLED:
            $statusColors[] = "#f39c12";
            break;
        case OilInventory::STATUS_ACCEPTED:
            $statusColors[] = "#00a65a";
            break;
        case OilInventory::STATUS_REJECTED:
            $statusColors[] = "#dd4b39";
            break;
        default:
            $statusColors[] = "#999999";
    }
}

$monthNames = [
    1 => "Янв",
    2 => "Фев",
    3 => "Мар",
    4 => "Апр",
    5 => "Май",
    6 => "Июн",
    7 => "Июл",
    8 => "Авг",
    9 => "Сен",
    10 => "Окт",
    11 => "Ноя",
    12 => "Дек",
];
?>

<div class="oil-inventory-dashboard">
    <h1>
        <?= Html::encode($this->title) ?>
        <small>Панель аналитики</small>
    </h1>

    <!-- Фильтры -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <div class="well well-sm">
                <?php $form = ActiveForm::begin([
                    "method" => "get",
                    "options" => ["class" => "form-inline"],
                ]); ?>

                <div class="form-group" style="margin-right: 15px;">
                    <?= Html::label("С:", "date_from", [
                        "class" => "control-label",
                        "style" => "margin-right: 5px;",
                    ]) ?>
                    <?= Html::input("date", "date_from", $dateFrom, [
                        "class" => "form-control",
                        "style" => "width: 140px;",
                    ]) ?>
                </div>

                <div class="form-group" style="margin-right: 15px;">
                    <?= Html::label("По:", "date_to", [
                        "class" => "control-label",
                        "style" => "margin-right: 5px;",
                    ]) ?>
                    <?= Html::input("date", "date_to", $dateTo, [
                        "class" => "form-control",
                        "style" => "width: 140px;",
                    ]) ?>
                </div>

                <?php if ($isManager): ?>
                <div class="form-group" style="margin-right: 15px;">
                    <?= Html::label("Магазин:", "store_id", [
                        "class" => "control-label",
                        "style" => "margin-right: 5px;",
                    ]) ?>
                    <?= Select2::widget([
                        "name" => "store_id",
                        "value" => $storeFilter,
                        "data" => ArrayHelper::map($storesList, "id", "name"),
                        "options" => [
                            "placeholder" => "Все магазины",
                            "style" => "width: 320px;",
                        ],
                        "pluginOptions" => [
                            "allowClear" => true,
                            "minimumInputLength" => 0,
                            "dropdownAutoWidth" => true,
                            "width" => "100%",
                            "language" => [
                                "errorLoading" => new \yii\web\JsExpression(
                                    "function () { return 'Загрузка...'; }",
                                ),
                                "inputTooShort" => new \yii\web\JsExpression(
                                    "function () { return 'Введите хотя бы 1 символ'; }",
                                ),
                                "noResults" => new \yii\web\JsExpression(
                                    "function () { return 'Ничего не найдено'; }",
                                ),
                                "searching" => new \yii\web\JsExpression(
                                    "function () { return 'Поиск...'; }",
                                ),
                            ],
                            "matcher" => new \yii\web\JsExpression('
                                function(params, data) {
                                    // Если нет поискового запроса, показываем все
                                    if (!params.term || params.term.trim() === "") {
                                        return data;
                                    }

                                    // Если нет текста у элемента, пропускаем
                                    if (!data.text) {
                                        return null;
                                    }

                                    var term = params.term.trim().toLowerCase();
                                    var text = data.text.toLowerCase();

                                    // Функция для транслитерации
                                    function transliterate(str) {
                                        var ru = "абвгдеёжзийклмнопрстуфхцчшщъыьэюя";
                                        var en = ["a","b","v","g","d","e","yo","zh","z","i","y","k","l","m","n","o","p","r","s","t","u","f","h","ts","ch","sh","sch","","y","","e","yu","ya"];
                                        var result = str;

                                        // Русский в латиницу
                                        for (var i = 0; i < ru.length; i++) {
                                            result = result.split(ru[i]).join(en[i] || "");
                                        }

                                        return result;
                                    }

                                    // Функция для обратной транслитерации (латиница в кириллицу)
                                    function reverseTransliterate(str) {
                                        var replacements = {
                                            "a": "а", "b": "б", "v": "в", "g": "г", "d": "д",
                                            "e": "е", "z": "з", "i": "и", "y": "й", "k": "к",
                                            "l": "л", "m": "м", "n": "н", "o": "о", "p": "п",
                                            "r": "р", "s": "с", "t": "т", "u": "у", "f": "ф",
                                            "h": "х", "c": "ц", "x": "кс"
                                        };

                                        var result = str;
                                        for (var key in replacements) {
                                            result = result.split(key).join(replacements[key]);
                                        }

                                        return result;
                                    }

                                    // Проверяем прямое совпадение
                                    if (text.indexOf(term) > -1) {
                                        return data;
                                    }

                                    // Проверяем транслитерацию (русский текст, латинский поиск)
                                    var transliteratedText = transliterate(text);
                                    if (transliteratedText.indexOf(term) > -1) {
                                        return data;
                                    }

                                    // Проверяем обратную транслитерацию (латинский текст, русский поиск)
                                    var reversedTerm = reverseTransliterate(term);
                                    if (text.indexOf(reversedTerm) > -1) {
                                        return data;
                                    }

                                    // Проверяем транслитерацию поискового запроса
                                    var transliteratedTerm = transliterate(term);
                                    if (text.indexOf(transliteratedTerm) > -1) {
                                        return data;
                                    }

                                    return null;
                                }
                            '),
                        ],
                    ]) ?>
                </div>
                <?php endif; ?>

                <?= Html::submitButton(
                    '<i class="fa fa-search"></i> Применить',
                    [
                        "class" => "btn btn-primary",
                    ],
                ) ?>
                <?= Html::a(
                    '<i class="fa fa-refresh"></i> Сбросить',
                    ["/oil-inventory-dashboard/index"],
                    [
                        "class" => "btn btn-default",
                        "style" => "margin-left: 5px;",
                    ],
                ) ?>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <?php if ($totalRecords == 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4><i class="icon fa fa-info-circle"></i> Данные отсутствуют</h4>
                    <p style="margin-bottom: 15px;">
                        <?php if ($storeFilter || $dateFrom || $dateTo): ?>
                            <strong>Нет данных с выбранными фильтрами.</strong> Попробуйте изменить параметры фильтров.
                        <?php else: ?>
                            <strong>Нет данных для аналитики.</strong> Создайте записи учета масла для просмотра аналитики.
                        <?php endif; ?>
                    </p>
                    <div class="btn-group">
                        <?php if (!$storeFilter && !$dateFrom && !$dateTo): ?>
                            <?= Html::a(
                                '<i class="fa fa-plus"></i> Создать запись',
                                ["/oil-inventory/create"],
                                [
                                    "class" => "btn btn-sm",
                                    "style" =>
                                        "background-color: white; color: #333; border: 1px solid #ddd;",
                                ],
                            ) ?>
                        <?php endif; ?>
                        <?= Html::a(
                            '<i class="fa fa-list"></i> К списку',
                            ["/oil-inventory/index"],
                            [
                                "class" => "btn btn-sm",
                                "style" =>
                                    "background-color: white; color: #333; border: 1px solid #ddd;",
                            ],
                        ) ?>
                        <?php if ($storeFilter || $dateFrom || $dateTo): ?>
                            <?= Html::a(
                                '<i class="fa fa-refresh"></i> Сбросить',
                                ["/oil-inventory-dashboard/index"],
                                [
                                    "class" => "btn btn-sm",
                                    "style" =>
                                        "background-color: white; color: #333; border: 1px solid #ddd;",
                                ],
                            ) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

    <!-- Основные метрики -->
    <?php if ($storeFilter || $dateFrom || $dateTo): ?>
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <strong><i class="fa fa-filter"></i> Применены фильтры:</strong>
        <?php if ($storeFilter): ?>
            <?php
            $storeName = $storeFilter;
            if ($isManager && $storesList) {
                foreach ($storesList as $store) {
                    if ($store["id"] == $storeFilter) {
                        $storeName = $store["name"];
                        break;
                    }
                }
            }
            ?>
            <span class="label label-default"><?= Html::encode(
                $storeName,
            ) ?></span>
        <?php endif; ?>
        <?php if ($dateFrom): ?>
            <span class="label label-default">с <?= date(
                "d.m.Y",
                strtotime($dateFrom),
            ) ?></span>
        <?php endif; ?>
        <?php if ($dateTo): ?>
            <span class="label label-default">по <?= date(
                "d.m.Y",
                strtotime($dateTo),
            ) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Предупреждение о незаполненных записях -->
    <?php if (!empty($unfilledRecords)): ?>
    <div class="alert alert-warning" style="margin-bottom: 20px; background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true" style="color: #8a6d3b; opacity: 0.5;">&times;</button>
        <h4 style="color: #8a6d3b;"><i class="icon fa fa-warning"></i> Внимание! Обнаружены незаполненные записи</h4>
        <p style="margin-bottom: 15px; color: #8a6d3b;">
            Следующие записи имеют незаполненное поле "Аппарат" (значение = 0). Пожалуйста, проверьте и заполните эти записи:
        </p>
        <div class="table-responsive">
            <table class="table table-condensed table-striped" style="background-color: #ffffff; border: 1px solid #faebcc;">
                <thead style="background-color: #faf2cc;">
                    <tr>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Дата</th>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Магазин</th>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Остаток на начало (л)</th>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Приход (л)</th>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Новое масло (л)</th>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Статус</th>
                        <th style="color: #8a6d3b; border-bottom: 2px solid #faebcc;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $maxRecordsToShow = 10;
                    $recordCount = 0;
                    foreach ($unfilledRecords as $record):

                        if ($recordCount >= $maxRecordsToShow) {
                            break;
                        }
                        $recordCount++;
                        ?>
                        <tr style="background-color: #fff;">
                            <td style="white-space: nowrap; color: #333;">
                                <strong><?= Yii::$app->formatter->asDate(
                                    $record->created_at,
                                    "php:d.m.Y",
                                ) ?></strong>
                                <br>
                                <small style="color: #666;"><?= Yii::$app->formatter->asTime(
                                    $record->created_at,
                                    "php:H:i",
                                ) ?></small>
                            </td>
                            <td style="white-space: nowrap; color: #333;">
                                <?= Html::encode(
                                    $record->store->name ?? "Не указан",
                                ) ?>
                            </td>
                            <td class="text-right" style="color: #333;">
                                <?= number_format(
                                    $record->opening_balance,
                                    3,
                                ) ?>
                            </td>
                            <td class="text-right" style="color: #333;">
                                <?= number_format($record->income, 3) ?>
                            </td>
                            <td class="text-right" style="color: #333;">
                                <?= number_format($record->new_oil, 3) ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php
                                $statusColors = [
                                    OilInventory::STATUS_NEW => "label-info",
                                    OilInventory::STATUS_FILLED =>
                                        "label-warning",
                                    OilInventory::STATUS_REJECTED =>
                                        "label-danger",
                                    OilInventory::STATUS_ACCEPTED =>
                                        "label-success",
                                ];
                                $colorClass =
                                    $statusColors[$record->status] ??
                                    "label-default";
                                ?>
                                <span class="label <?= $colorClass ?>">
                                    <?= $record->getStatusLabel() ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <?= Html::a(
                                    '<i class="fa fa-eye"></i> Просмотр',
                                    [
                                        "/oil-inventory/view",
                                        "id" => $record->id,
                                    ],
                                    [
                                        "class" => "btn btn-xs btn-info",
                                        "title" => "Просмотр",
                                    ],
                                ) ?>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                    ?>
                </tbody>
            </table>
            <?php if (count($unfilledRecords) > $maxRecordsToShow): ?>
                <div class="text-center" style="margin-top: 10px;">
                    <em style="color: #8a6d3b;">Показаны первые <?= $maxRecordsToShow ?> записей из <?= count(
     $unfilledRecords,
 ) ?> незаполненных.</em>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
                <!-- Последние записи -->
                <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-clock-o"></i> Последние записи
                    </h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Магазин</th>
                                    <th class="text-right">Остаток на начало (л)</th>
                                    <th class="text-right">Приход (л)</th>
                                    <th class="text-right">Возврат (кг)</th>
                                    <th class="text-right">Аппарат (л)</th>
                                    <th class="text-right">Новое масло (л)</th>
                                    <th class="text-right">Испарение (л)</th>
                                    <th class="text-right">Остаток на конец (л)</th>
                                    <th>Статус</th>
                                    <th class="text-center">Изменения</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRecords as $record): ?>
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            <?= Yii::$app->formatter->asDate(
                                                $record->created_at,
                                                "php:d.m.Y",
                                            ) ?>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <?= Html::encode(
                                                $record->store->name ??
                                                    "Не указан",
                                            ) ?>
                                        </td>
                                        <td class="text-right">
                                            <?= number_format(
                                                $record->opening_balance,
                                                3,
                                            ) ?>
                                        </td>
                                        <td class="text-right">
                                            <?= number_format(
                                                $record->income,
                                                3,
                                            ) ?>
                                        </td>
                                        <td class="text-right">
                                            <strong><?= number_format(
                                                $record->return_amount_kg,
                                                3,
                                            ) ?> кг</strong><br>
                                            <small class="text-muted">(<?= number_format(
                                                $record->return_amount,
                                                3,
                                            ) ?> л)</small>
                                        </td>
                                        <td class="text-right">
                                            <?= number_format(
                                                $record->apparatus,
                                                3,
                                            ) ?>
                                        </td>
                                        <td class="text-right">
                                            <?= number_format(
                                                $record->new_oil,
                                                3,
                                            ) ?>
                                        </td>
                                        <td class="text-right">
                                            <span class="<?= $record->evaporation >
                                            0
                                                ? "text-danger"
                                                : "text-success" ?>">
                                                <?= number_format(
                                                    $record->evaporation,
                                                    3,
                                                ) ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <strong><?= number_format(
                                                $record->closing_balance,
                                                3,
                                            ) ?></strong>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <?php
                                            $statusColors = [
                                                OilInventory::STATUS_NEW =>
                                                    "label-info",
                                                OilInventory::STATUS_FILLED =>
                                                    "label-warning",
                                                OilInventory::STATUS_REJECTED =>
                                                    "label-danger",
                                                OilInventory::STATUS_ACCEPTED =>
                                                    "label-success",
                                            ];
                                            $colorClass =
                                                $statusColors[
                                                    $record->status
                                                ] ?? "label-default";
                                            ?>
                                            <span class="label <?= $colorClass ?>">
                                                <?= $record->getStatusLabel() ?>
                                            </span>
                                        </td>
                                        <td class="text-center" style="white-space: nowrap;">
                                            <?php if ($record->changes_count > 0): ?>
                                                <a href="javascript:void(0);"
                                                   class="show-history-btn"
                                                   data-record-id="<?= $record->id ?>"
                                                   title="Показать историю изменений">
                                                    <span class="label label-warning">
                                                        <?= $record->changes_count ?>
                                                    </span>
                                                </a>
                                            <?php else: ?>
                                                <span class="label label-default">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <?= Html::a(
                                                '<i class="fa fa-eye"></i>',
                                                [
                                                    "/oil-inventory/view",
                                                    "id" => $record->id,
                                                ],
                                                [
                                                    "class" =>
                                                        "btn btn-xs btn-info",
                                                    "title" => "Просмотр",
                                                ],
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3><?= $totalRecords ?></h3>
                    <p>Всего записей</p>
                </div>
                <a href="<?= Url::to([
                    "/oil-inventory/index",
                ]) ?>" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3><?= number_format(
                        $averageConsumption["avg_total_consumption"],
                        2,
                    ) ?></h3>
                    <p>Средний расход/день (л)</p>
                </div>
                <a href="#consumption-analysis" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3><?= number_format(
                        $wastageAnalysis["avg_evaporation"] ?? 0,
                        2,
                    ) ?></h3>
                    <p>Среднее испарение/день (л)</p>
                </div>
                <a href="#wastage-analysis" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3><?= number_format(
                        $efficiencyMetrics["evaporation_percentage"] ?? 0,
                        1,
                    ) ?>%</h3>
                    <p>Доля испарения</p>
                </div>
                <a href="#efficiency-metrics" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Дополнительные метрики -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box" style="background-color: #3c8dbc; color: #fff;">
                <div class="inner">
                    <h3><?= number_format(
                        $averageConsumption["avg_apparatus"],
                        2,
                    ) ?></h3>
                    <p>Средний расход аппарата (л)</p>
                </div>
                <a href="#consumption-analysis" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box" style="background-color: #00a65a; color: #fff;">
                <div class="inner">
                    <h3><?= number_format(
                        $averageConsumption["avg_new_oil"],
                        2,
                    ) ?></h3>
                    <p>Среднее новое масло (л)</p>
                </div>
                <a href="#consumption-analysis" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box" style="background-color: #dd4b39; color: #fff;">
                <div class="inner">
                    <h3><?= number_format(
                        $averageConsumption["avg_return"],
                        2,
                    ) ?></h3>
                    <p>Средний возврат/день (л)</p>
                </div>
                <a href="#efficiency-metrics" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-xs-6">
            <div class="small-box" style="background-color: #605ca8; color: #fff;">
                <div class="inner">
                    <h3><?= number_format(
                        $efficiencyMetrics["return_percentage"] ?? 0,
                        1,
                    ) ?>%</h3>
                    <p>Доля возврата</p>
                </div>
                <a href="#efficiency-metrics" class="small-box-footer">
                    Подробнее <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Графики и аналитика -->
    <div class="row">
        <!-- Статистика по статусам -->
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Распределение по статусам
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Тренд за неделю -->
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Тренд остатков
                        <?php if ($dateFrom && $dateTo): ?>
                            (<?= date(
                                "d.m.Y",
                                strtotime($dateFrom),
                            ) ?> - <?= date("d.m.Y", strtotime($dateTo)) ?>)
                        <?php elseif ($dateFrom): ?>
                            (с <?= date("d.m.Y", strtotime($dateFrom)) ?>)
                        <?php elseif ($dateTo): ?>
                            (до <?= date("d.m.Y", strtotime($dateTo)) ?>)
                        <?php else: ?>
                            (7 дней)
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="weeklyTrendChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблицы аналитики -->
    <div class="row">
        <!-- Топ дней по расходу -->
        <div class="col-md-6">
            <div class="box box-warning" id="consumption-analysis">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Топ дней по расходу
                    </h3>
                </div>
                <div class="box-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th class="text-right">Общий расход (л)</th>
                                <th class="text-right">Аппарат (л)</th>
                                <th class="text-right">Новое масло (л)</th>
                                <th class="text-right">Испарение (л)</th>
                                <th class="text-right">Возврат (л)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topConsumptionDays as $day): ?>
                                <tr>
                                    <td><?= date(
                                        "d.m.Y",
                                        strtotime($day["date"]),
                                    ) ?></td>
                                    <td class="text-right">
                                        <strong><?= number_format(
                                            $day["total_consumption"],
                                            3,
                                        ) ?></strong>
                                    </td>
                                    <td class="text-right"><?= number_format(
                                        $day["apparatus"],
                                        3,
                                    ) ?></td>
                                    <td class="text-right"><?= number_format(
                                        $day["new_oil"],
                                        3,
                                    ) ?></td>
                                    <td class="text-right"><?= number_format(
                                        $day["evaporation"],
                                        3,
                                    ) ?></td>
                                    <td class="text-right"><?= number_format(
                                        $day["return_amount"],
                                        3,
                                    ) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>

    <!-- Месячная статистика -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Месячная статистика
                    </h3>
                </div>
                <div class="box-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Месяц</th>
                                <th class="text-right">Записей</th>
                                <th class="text-right">Средний остаток</th>
                                <th class="text-right">Общий приход</th>
                                <th class="text-right">Общий расход</th>
                                <th class="text-right">Эффективность</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyStats as $month): ?>
                                <tr>
                                    <td>
                                        <?= $monthNames[
                                            $month["month"]
                                        ] ?> <?= $month["year"] ?>
                                    </td>
                                    <td class="text-right"><?= $month[
                                        "records_count"
                                    ] ?></td>
                                    <td class="text-right"><?= number_format(
                                        $month["avg_closing"],
                                        3,
                                    ) ?></td>
                                    <td class="text-right"><?= number_format(
                                        $month["total_income"],
                                        3,
                                    ) ?></td>
                                    <td class="text-right"><?= number_format(
                                        $month["total_consumption"],
                                        3,
                                    ) ?></td>
                                    <td class="text-right">
                                        <?php
                                        $efficiency =
                                            $month["total_consumption"] > 0
                                                ? ($month["total_income"] /
                                                        $month[
                                                            "total_consumption"
                                                        ]) *
                                                    100
                                                : 0;
                                        $efficiencyClass =
                                            $efficiency >= 100
                                                ? "text-success"
                                                : ($efficiency >= 80
                                                    ? "text-warning"
                                                    : "text-danger");
                                        ?>
                                        <span class="<?= $efficiencyClass ?>">
                                            <?= number_format(
                                                $efficiency,
                                                1,
                                            ) ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Анализ потерь и эффективности -->
    <div class="row">
        <div class="col-md-6" id="wastage-analysis">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Анализ потерь
                    </h3>
                </div>
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr>
                            <td><strong>Среднее испарение:</strong></td>
                            <td class="text-right"><?= number_format(
                                $wastageAnalysis["avg_evaporation"] ?? 0,
                                3,
                            ) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Максимальное испарение:</strong></td>
                            <td class="text-right"><?= number_format(
                                $wastageAnalysis["max_evaporation"] ?? 0,
                                3,
                            ) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Минимальное испарение:</strong></td>
                            <td class="text-right"><?= number_format(
                                $wastageAnalysis["min_evaporation"] ?? 0,
                                3,
                            ) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Общее испарение:</strong></td>
                            <td class="text-right"><?= number_format(
                                $wastageAnalysis["total_evaporation"] ?? 0,
                                3,
                            ) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6" id="efficiency-metrics">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Метрики эффективности
                    </h3>
                </div>
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr>
                            <td><strong>Доля испарения:</strong></td>
                            <td class="text-right">
                                <span class="label label-warning">
                                    <?= number_format(
                                        $efficiencyMetrics[
                                            "evaporation_percentage"
                                        ] ?? 0,
                                        1,
                                    ) ?>%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Доля аппарата:</strong></td>
                            <td class="text-right">
                                <span class="label label-primary">
                                    <?= number_format(
                                        $efficiencyMetrics[
                                            "apparatus_percentage"
                                        ] ?? 0,
                                        1,
                                    ) ?>%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Доля нового масла:</strong></td>
                            <td class="text-right">
                                <span class="label label-info">
                                    <?= number_format(
                                        $efficiencyMetrics[
                                            "new_oil_percentage"
                                        ] ?? 0,
                                        1,
                                    ) ?>%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Доля возврата:</strong></td>
                            <td class="text-right">
                                <span class="label label-danger">
                                    <?= number_format(
                                        $efficiencyMetrics[
                                            "return_percentage"
                                        ] ?? 0,
                                        1,
                                    ) ?>%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Эффективность прихода:</strong></td>
                            <td class="text-right">
                                <span class="label label-success">
                                    <?= number_format(
                                        $efficiencyMetrics[
                                            "income_efficiency"
                                        ] ?? 0,
                                        1,
                                    ) ?>%
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Средний расход по категориям -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Средний расход по категориям
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="consumptionChart" width="800" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для истории изменений -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-history"></i> История изменений записи
                </h4>
            </div>
            <div class="modal-body" id="historyModalBody">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Загрузка истории...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// График статусов
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: <?= json_encode($statusColors) ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// График недельного тренда
const weeklyCtx = document.getElementById('weeklyTrendChart').getContext('2d');
new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($weeklyTrend, "date")) ?>,
        datasets: [{
            label: 'Остаток на конец дня',
            data: <?= json_encode(
                array_column($weeklyTrend, "closing_balance"),
            ) ?>,
            borderColor: '#00a65a',
            backgroundColor: 'rgba(0, 166, 90, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// График среднего расхода
const consumptionCtx = document.getElementById('consumptionChart').getContext('2d');
new Chart(consumptionCtx, {
    type: 'bar',
    data: {
        labels: ['Аппарат', 'Новое масло', 'Испарение', 'Возврат'],
        datasets: [{
            label: 'Средний расход',
            data: [
                <?= $averageConsumption["avg_apparatus"] ?>,
                <?= $averageConsumption["avg_new_oil"] ?>,
                <?= $averageConsumption["avg_evaporation"] ?>,
                <?= $averageConsumption["avg_return"] ?>
            ],
            backgroundColor: ['#3c8dbc', '#00a65a', '#f39c12', '#dd4b39']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Обработчик клика по кнопке "История изменений"
document.addEventListener('DOMContentLoaded', function() {
    const historyButtons = document.querySelectorAll('.show-history-btn');

    historyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const recordId = this.getAttribute('data-record-id');
            loadHistory(recordId);
        });
    });
});

function loadHistory(recordId) {
    // Показываем модальное окно
    $('#historyModal').modal('show');

    // Сбрасываем содержимое на загрузку
    document.getElementById('historyModalBody').innerHTML = `
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p>Загрузка истории...</p>
        </div>
    `;

    // Загружаем данные истории через AJAX
    fetch('<?= Url::to(['/oil-inventory/history']) ?>?id=' + recordId)
        .then(response => response.json())
        .then(data => {
            displayHistory(data);
        })
        .catch(error => {
            document.getElementById('historyModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    Ошибка загрузки истории: ${error.message}
                </div>
            `;
        });
}

function displayHistory(history) {
    if (!history || history.length === 0) {
        document.getElementById('historyModalBody').innerHTML = `
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                История изменений отсутствует.
            </div>
        `;
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                    <tr>
                        <th width="15%">Дата и время</th>
                        <th width="15%">Пользователь</th>
                        <th width="20%">Поле</th>
                        <th width="20%">Старое значение</th>
                        <th width="20%">Новое значение</th>
                        <th width="10%">Действие</th>
                    </tr>
                </thead>
                <tbody>
    `;

    history.forEach(item => {
        const actionBadge = item.action === 'create' ? 'label-success' :
                           item.action === 'update' ? 'label-warning' :
                           'label-danger';

        html += `
            <tr>
                <td style="white-space: nowrap;">${item.created_at}</td>
                <td>${item.user_name || 'N/A'}</td>
                <td><strong>${item.field_label}</strong></td>
                <td>${item.old_value || '<em class="text-muted">пусто</em>'}</td>
                <td>${item.new_value || '<em class="text-muted">пусто</em>'}</td>
                <td><span class="label ${actionBadge}">${item.action_label}</span></td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('historyModalBody').innerHTML = html;
}
</script>

<style>
.small-box {
    border-radius: 2px;
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
}

.small-box > .inner {
    padding: 10px;
}

.small-box > .small-box-footer {
    position: relative;
    text-align: center;
    padding: 3px 0;
    color: #fff;
    color: rgba(255,255,255,0.8);
    display: block;
    z-index: 10;
    background: rgba(0,0,0,0.1);
    text-decoration: none;
}

.small-box .icon {
    -webkit-transition: all .3s linear;
    -o-transition: all .3s linear;
    transition: all .3s linear;
    position: absolute;
    top: -10px;
    right: 10px;
    z-index: 0;
    font-size: 90px;
    color: rgba(0,0,0,0.15);
}

.bg-aqua {
    background-color: #00c0ef !important;
    color: #fff;
}

.bg-green {
    background-color: #00a65a !important;
    color: #fff;
}

.bg-yellow {
    background-color: #f39c12 !important;
    color: #fff;
}

.bg-red {
    background-color: #dd4b39 !important;
    color: #fff;
}
</style>

<?php endif; ?>
