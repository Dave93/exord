<?php

use app\models\Dashboard;
use app\models\OrderItems;
use app\models\Orders;

/* @var $this yii\web\View */
/* @var $model Orders */

$i = 0;
$total = 0;
$items = OrderItems::find()->where('orderId=:id and storeQuantity>0', [':id' => $model->id])->all();
$products = Orders::getOrderProducts($model->id);
$groupedProducts = [];
foreach ($products as $product) {
    if (empty($groupedProducts[$product['groupName']])) {
        $groupedProducts[$product['groupName']] = [
            'name' => $product['groupName'],
            'products' => []
        ];
    }
    $groupedProducts[$product['groupName']]['products'][] = $product;
}
uasort($groupedProducts, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>
<div style="font-size: 20px; font-weight: bold">РАСХОДНАЯ НАКЛАДНАЯ</div>
<div>Номер документа: <?= $model->id ?></div>
<div>Дата документа: <?= date('d.m.Y', strtotime($model->date)) ?></div>
<div>Поставщик: Центральный склад Chain</div>
<div>Получатель: <?= $model->store->name ?></div>
<div>Примечание: <?= $model->comment ? $model->comment : '______________________________________' ?></div>

<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">

    <table border="1" width="100%" cellpadding="0" cellspacing="0">
        <thead>
        <tr>
            <th>№</th>
            <th align="center">Продукт</th>
            <th width="30" align="center">Ед. изм.</th>
            <th width="100" align="center">Кол-во</th>
            <th align="center">Готовность</th>
        </tr>
        </thead>
        <tbody>
            <? foreach ($groupedProducts as $group) {?>
                <tr>
                    <th colspan="4" class="text-center"><?=($group['name'] ? $group['name'] : 'Остальные продукты')?></th>
                </tr>
                <?php foreach ($group['products'] as $item): ?>
                    <?php
                    $i++;
                    $price = $item['price'] * (100 + $model->user->percentage) / 100;
                    $priceString = Dashboard::price($price);
                    $sum = $item['storeQuantity'] * $price;
                    $total += $sum;
                    ?>
                    <tr>
                        <td width="20" align="center"><?= $i ?></td>
                        <td class="ml-2"><?= $item['name'] ?></td>
                        <td width="30" align="center"><?= $item['mainUnit'] ?></td>
                        <td width="100" align="center"><?= $item['storeQuantity'] ?></td>
                        <td align="center"></td>
                    </tr>
                <?php endforeach; ?>
            <?}?>
        </tbody>
    </table>