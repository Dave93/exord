<?php

use app\models\Dashboard;
use yii\helpers\Html;

?>
<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('site', 'index') ? 'active' : '' ?>">
        <?= Html::a('Главная', ['/']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('orders', 'customer-orders') ? 'active' : '' ?>">
        <?= Html::a('Заказы', ['orders/customer-orders'], ['class' => 'nav-link']) ?>
    </li>
    <?php /*
    <li class="<?= Dashboard::isNavActive('products-usage', 'create-usage') ? 'active' : '' ?>">
        <?= Html::a('Использование товаров', ['products-usage/create-usage'], ['class' => 'nav-link']) ?>
    </li>
*/?>

    <li class="<?= Dashboard::isNavActive('orders', 'customer-history') ? 'active' : '' ?>">
        <?= Html::a('Архив', ['orders/customer-history'], ['class' => 'nav-link']) ?>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>