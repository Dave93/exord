<?php

/* @var $this yii\web\View */

use app\models\User;

$this->title = "Панель управления";
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header text-center">
        <h4 class="title">Добро пожаловать <?= Yii::$app->user->identity->fullname ?></h4>
        <p class="category"><?= User::$roles[Yii::$app->user->identity->role] ?></p>
    </div>
    <div class="content"></div>
</div>