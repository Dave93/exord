<?php

use app\models\Dashboard;
use yii\helpers\Html;

?>
<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('site', 'index') ? 'active' : '' ?>">
        <?= Html::a('Главная', ['/']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('orders', 'index') ? 'active' : '' ?>">
        <?= Html::a('Заказы', ['orders/index']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('orders', 'stock') ? 'active' : '' ?>">
        <?= Html::a('Заказы на сегодня', ['orders/stock'], ['class' => 'nav-link']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('market-orders', 'stock') ? 'active' : '' ?>">
        <?= Html::a('Заказы на базар', ['market-orders/stock'], ['class' => 'nav-link']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('invoice', 'index') ? 'active' : '' ?>">
        <?= Html::a('Накадная', ['invoice/index']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('stores') ? 'active' : '' ?>">
        <?= Html::a('Склады', ['stores/index']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('products') ? 'active' : '' ?>">
        <?= Html::a('Продукты', ['products/index']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('product-groups') ? 'active' : '' ?>">
        <?= Html::a('Группы продуктов', ['product-groups/index']) ?>
    </li>
<!--    <li class="--><?//= Dashboard::isNavActive('products') ? 'active' : '' ?><!--">-->
<!--        --><?//= Html::a('История использование товаров', ['/']) ?>
<!--    </li>-->
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">Отчеты <span class="caret"></span></a>
        <ul class="dropdown-menu">
            <li><?= Html::a('Изменение цен', ['report/price']) ?></li>
            <li><?= Html::a('Расходы', ['report/spending']) ?></li>
        </ul>
    </li>
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">Другие <span class="caret"></span></a>
        <ul class="dropdown-menu">
            <li><?= Html::a('Зона', ['zone/index']) ?></li>
            <li><?= Html::a('Филиалы', ['departments/index']) ?></li>
            <li><?= Html::a('Поставщики', ['suppliers/index']) ?></li>
            <li><?= Html::a('Пользователи', ['user/index']) ?></li>
            <li><?= Html::a('Telegram Пользователи', ['tgusers/index']) ?></li>
            <li><?= Html::a('Приём масла', ['/oil-inventory/filled']) ?></li>
            <li><?= Html::a('Сводка возврата масла', ['/oil-returns-summary/index']) ?></li>
            <li><?= Html::a('Списания заказов', ['write-offs/index']) ?></li>
            <li><?= Html::a('Списания продуктов', ['product-writeoff/admin-index']) ?></li>
            <li><?= Html::a('Привязка товаров', ['products/links']) ?></li>
            <li><?= Html::a('Настройки', ['settings/index']) ?></li>
            <li><?= Html::a('Настройка наличия', ['availability/index']) ?></li>
            <li><?= Html::a('Iiko Открытие заказы', ['pending-deliveries/index']) ?></li>
            <li><?= Html::a('Ограничения по времени', ['product-time-limitation/index']) ?></li>
        </ul>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>
