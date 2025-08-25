<?php
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */
$this->title = 'Инвентаризация масла #' . $model->id;
?>
<div>
<strong><?=$model->created_at?></strong>
    <strong><?=date(strtotime($model->created_at), "Y-m-d H:i:s")?></strong>
</div>
<div class="receipt-header">
    <strong><?= Html::encode($model->store->name ?? 'Магазин') ?></strong>
    <p><?= Yii::$app->formatter->asDate($model->created_at, 'php:d.m.Y') ?></p>
    <p><?= Yii::$app->formatter->asTime($model->created_at, 'php:H:i') ?></p>
</div>

<div class="receipt-body">
    <table>
        <tr>
            <td>ID:</td>
            <td class="text-right">#<?= $model->id ?></td>
        </tr>
        <tr>
            <td colspan="2"><div class="divider"></div></td>
        </tr>
        <tr>
            <td>Возврат кг:</td>
            <td class="text-right"><?= number_format($model->return_amount_kg, 2) ?></td>
        </tr>
        <tr>
            <td colspan="2"><div class="divider"></div></td>
        </tr>
    </table>
</div>

<div class="receipt-footer">
    <p>Статус: <?= Html::encode($model->getStatusLabel()) ?></p>
    <p>* * *</p>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
</div> 