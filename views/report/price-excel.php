<?php

use app\models\Dashboard;
use app\models\Suppliers;
use kartik\date\DatePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $start string */
/* @var $end string */
/* @var $product string */
/* @var $supplier string */
/* @var $data array */
/* @var $mean array */
/* @var $suppliers array */
/* @var $searchModel app\models\SupplierSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$charTitle = '';
$charData = '';
$dates = array_keys($data);
$ss = ArrayHelper::map(Suppliers::find()->asArray()->all(), 'id', 'name');
foreach ($dates as $d) {
    $m = $mean[$d];
}
?>
<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
<table border="1">
    <thead>
    <tr>
        <th class="text-nowrap">Наименование</th>
        <?php foreach ($dates as $d): ?>
            <th class="text-nowrap"><?= date('d.m.y', strtotime($d)) ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($suppliers as $s): ?>
        <tr>
            <td class="text-nowrap"><?= $ss[$s] ?></td>
            <?php foreach ($dates as $d): ?>
                <td class="text-nowrap"><?= Dashboard::clearPrice($data[$d][$s]) ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td class="text-nowrap">Среднее значение</td>
        <?php foreach ($dates as $d): ?>
            <td class="text-nowrap"><?= Dashboard::clearPrice($mean[$d]) ?></td>
        <?php endforeach; ?>
    </tr>
    </tbody>
</table>