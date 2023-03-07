<?php

use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Html;

$logo = Html::img(Yii::$app->homeUrl . 'img/logo_w.png', ['class' => 'img-responsive']);
NavBar::begin([
    'brandLabel' => $logo,
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-inverse navbar-fixed-top idocs-nav',
    ],
//        'innerContainerOptions' => ['class' => 'container-fluid'],
]);
echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => [
        ['label' => 'Главная', 'url' => ['/site/index']],
//        ['label' => 'Календарь', 'url' => ['/site/calendar']],
//        ['label' => 'Заказы', 'url' => ['/orders/index']],
        ['label' => 'Филиалы', 'url' => ['/departments/index']],
        ['label' => 'Поставщики', 'url' => ['/suppliers/index']],
        ['label' => 'Склады', 'url' => ['/stores/index']],
        ['label' => 'Продукты', 'url' => ['/products/index']],
        ['label' => 'Настройки', 'url' => ['/settings/index']],
        ['label' => 'Пользователи', 'url' => ['/user/index']],
        Yii::$app->user->isGuest ? (
        ['label' => 'Войти', 'url' => ['/site/login']]
        ) : (
            '<li>'
            . Html::beginForm(['/site/logout'], 'post')
            . Html::submitButton(
                'Выйти (' . Yii::$app->user->identity->username . ')',
                ['class' => 'btn btn-link logout']
            )
            . Html::endForm()
            . '</li>'
        )
    ],
]);
NavBar::end();
?>