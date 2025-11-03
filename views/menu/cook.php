<?php

use app\models\Dashboard;
use yii\helpers\Html;
echo Yii::$app->user->identity->id;
?>

<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('site', 'index') ? 'active' : '' ?>">
        <?= Html::a('Главная', ['/']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('orders', 'customer-orders') ? 'active' : '' ?>">
        <?= Html::a('Заказы', ['orders/customer-orders'], ['class' => 'nav-link']) ?>
    </li>

    <li class="<?= Dashboard::isNavActive('oil-inventory', 'index') ? 'active' : '' ?>">
        <?= Html::a('Масло', ['oil-inventory/index']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('product-writeoff', 'index') ? 'active' : '' ?>">
        <?= Html::a('Списания', ['product-writeoff/index']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('store-transfer', 'index') ? 'active' : '' ?>">
        <?= Html::a('Внутреннее перемещение', ['store-transfer/index']) ?>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>
