<?php

use app\models\Dashboard;
use yii\helpers\Html;
?>

<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('meal-orders', 'customer-orders') || Dashboard::isNavActive('meal-orders', 'create') || Dashboard::isNavActive('meal-orders', 'update') ? 'active' : '' ?>">
        <?= Html::a('Заказы на сегодня', ['meal-orders/customer-orders']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('meal-orders', 'index') ? 'active' : '' ?>">
        <?= Html::a('Заказы', ['meal-orders/index']) ?>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>
