<?php

use app\models\Dashboard;
use yii\helpers\Html;

?>
<ul class="nav navbar-nav navbar-right">
    <li class="<?= Dashboard::isNavActive('site', 'index') ? 'active' : '' ?>">
        <?= Html::a('Главная', ['/']) ?>
    </li>
    <li class="<?= Dashboard::isNavActive('product-writeoff', 'admin-index') ? 'active' : '' ?>">
        <?= Html::a('Списания', ['product-writeoff/admin-index']) ?>
    </li>
    <li>
        <?= Html::a('Выйти (' . Yii::$app->user->identity->username . ')', ['/logout'], ['data-method' => 'POST']) ?>
    </li>
</ul>
