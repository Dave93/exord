<?php


use yii\helpers\Html;
use app\models\Dashboard;
use app\models\Orders;
use app\models\Stores;
use app\models\User;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Ошибка';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
    </div>
    <hr>
    <div class="content">
        <div class="alert alert-danger" role="alert"><?=$message?></div>
    </div>
</div>
