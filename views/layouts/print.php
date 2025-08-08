<?php
use yii\helpers\Html;
/* @var $this \yii\web\View */
/* @var $content string */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            padding: 5mm 3mm 15mm 3mm; /* Добавлен отступ снизу */
            width: 58mm; /* Thermal printer width */
            min-height: auto;
        }
        .receipt {
            margin: 0;
            padding: 0;
        }
        .receipt-header, .receipt-footer {
            text-align: center;
            margin-bottom: 5px;
            padding: 3px 0;
            border-bottom: 1px dashed #000;
        }
        .receipt-footer {
            border-bottom: none;
            border-top: 1px dashed #000;
            margin-top: 5px;
            padding-bottom: 10mm; /* Дополнительный отступ снизу */
        }
        .receipt-body table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-body th, .receipt-body td {
            padding: 4px 0;
            font-size: 13px;
            font-weight: 700;
        }
        .receipt-body tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        strong {
            font-size: 16px;
            font-weight: 800;
        }
        p {
            margin: 3px 0;
            font-size: 14px;
            font-weight: 700;
        }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 3px 0;
        }
        @media print {
            body {
                width: 58mm;
                margin: 0;
                padding: 3mm 3mm 15mm 3mm;
            }
            .no-print {
                display: none;
            }
            @page {
                size: 58mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print();">
<?php $this->beginBody() ?>
    <div class="receipt">
        <?= $content ?>
    </div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?> 