<?php

use app\models\Dashboard;
use yii\helpers\Html;

?>
<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('site', 'index') ? 'active' : '' ?>">
        <?= Html::a('Главная', ['/']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('orders', 'stock') ? 'active' : '' ?>">
        <?= Html::a('Заказы на сегодня', ['orders/stock'], ['class' => 'nav-link']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('orders', 'stock-orders') ? 'active' : '' ?>">
        <?= Html::a('Заказы', ['orders/stock-orders'], ['class' => 'nav-link']) ?>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>