<?php

use app\models\Dashboard;
use yii\helpers\Html;
?>

<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('meal-orders', 'stock') ? 'active' : '' ?>">
        <?= Html::a('Заказы на сегодня', ['meal-orders/stock']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('meal-orders', 'index') ? 'active' : '' ?>">
        <?= Html::a('Заказы', ['meal-orders/index']) ?>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>
