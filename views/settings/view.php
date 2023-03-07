<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Settings */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Настройки', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад',Yii::$app->request->referrer, ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>
    <div class="content table-responsive">
        <div class="orders-list">
            <div class="row">
                <div class="col-md-offset-2 col-md-8">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
//                            'title',
                            'key',
                            'value',
                            'created',
                            [
                                'attribute' => 'author_id',
                                'value' => $model->author->fullname
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
