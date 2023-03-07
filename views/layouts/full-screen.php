<?php

/* @var $this \yii\web\View */

/* @var $content string */

use app\assets\AppAsset;
use app\assets\PanelAsset;
use app\models\Dashboard;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;

PanelAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta http-equiv="Cache-Control" content="public">
    <meta name="author" content="Азим Махмудов"/>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport'/>
    <meta name="viewport" content="width=device-width"/>

    <link rel="shortcut icon" href="<?= Yii::$app->homeUrl ?>favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?= Yii::$app->homeUrl ?>favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="57x57" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Yii::$app->homeUrl ?>icons/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= Yii::$app->homeUrl ?>icons/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Yii::$app->homeUrl ?>icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Yii::$app->homeUrl ?>icons/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= Yii::$app->homeUrl ?>icons/favicon-16x16.png">
    <link rel="manifest" href="<?= Yii::$app->homeUrl ?>icons/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?= Yii::$app->homeUrl ?>icons/ms-icon-144x144.png">
    <meta name="theme-color" content="#d5302c">

    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> | EXORD</title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="wrapper">
    <div class="main-panel full-width">
        <nav class="navbar navbar-default navbar-fixed navbar-red">
            <div class="container-fluid">
                <div class="navbar-header nav-logo">
                    <?= Html::a(Html::img(Yii::$app->homeUrl . "img/logo.png"), ['site/index']) ?>
                </div>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="#">Заказы</a>
                        </li>
                        <li>
                            <?= Html::a('<p>Выйти (' . Yii::$app->user->identity->username . ')</p>', ['/logout'], ['data-method' => 'POST']) ?>
                        </li>
                        <li class="separator hidden-lg"></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="content">
            <div class="container-fluid">
                <?= $content ?>
            </div>
        </div>

        <footer class="footer">
            <div class="container-fluid">
                <p class="copyright pull-right">
                    &copy; <?= date("Y") ?> <a href="https://www.botagent.uz">Botagent</a> сделано с любовью
                </p>
            </div>
        </footer>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
