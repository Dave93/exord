<?php

use app\models\Dashboard;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $start string */
/* @var $end string */
/* @var $coefficient string */
/* @var $data array */
$i = 0;
?>
<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
<table border="1">
    <thead>
    <tr>
        <th>#</th>
        <th>Название</th>
        <th class="text-center">Ед. Изм.</th>
        <th class="text-right">Расход</th>
        <th class="text-right">Остаток</th>
        <th class="text-right">Закуп</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($data as $row): ?>
        <?php
        $i++;
        if ($row['stock'] > $row['total'] * $coefficient)
            $r = 0;
        else
            $r = round(($row['total'] * $coefficient - $row['stock']));
        ?>
        <tr>
            <td width="40"><?= $i ?></td>
            <td><?= $row['name'] ?></td>
            <td class="text-center"><?= $row['unit'] ?></td>
            <td class="text-right"><?= $row['total'] ?></td>
            <td class="text-right"><?= $row['stock'] ?></td>
            <td class="text-right"><?= $r ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>