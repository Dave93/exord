<?php
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */
$this->title = 'Чек #' . $model->id;
?>
<div class="receipt-header">
    <strong><?= Html::encode(Yii::$app->name) ?></strong><br>
    <p>Магазин: <?= Html::encode($model->store->name) ?></p>
    <p>Дата: <?= Yii::$app->formatter->asDatetime($model->created_at, 'php:d.m.Y H:i') ?></p>
</div>

<div class="receipt-body">
    <table>
        <tr>
            <td>Возврат (кг):</td>
            <td class="text-right"><?= number_format($model->return_amount_kg, 3) ?></td>
        </tr>
        <tr>
            <td>Возврат (л):</td>
            <td class="text-right"><?= number_format($model->return_amount, 3) ?></td>
        </tr>
    </table>
</div>

<div class="receipt-footer">
    <p>Статус: <?= Html::encode($model->getStatusLabel()) ?></p>
    <p>Спасибо!</p>
</div> 