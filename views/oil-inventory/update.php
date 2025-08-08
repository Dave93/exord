<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */

$this->title = 'Редактировать запись: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Учет масла', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактировать';
?>
<div class="oil-inventory-update">

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <?= Html::encode($this->title) ?>
                    </h3>
                </div>

                <div class="box-body">
                    <?= $this->render('_form', [
                        'model' => $model,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div> 