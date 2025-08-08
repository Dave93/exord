<?php

use yii\helpers\Html;
use app\models\OilInventory;

/* @var $this yii\web\View */
/* @var $model app\models\OilInventory */
/* @var $incompleteRecord app\models\OilInventory */

$this->title = 'Создать запись учета масла';
$this->params['breadcrumbs'][] = ['label' => 'Учет масла', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="oil-inventory-create">

    <?php if ($incompleteRecord): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4><i class="icon fa fa-warning"></i> Внимание!</h4>
                    <p>
                        <strong>Необходимо сначала заполнить данные за предыдущий день!</strong>
                    </p>
                    <p>
                        У вас есть незаполненная запись от <strong><?= Yii::$app->formatter->asDate($incompleteRecord->created_at, 'php:d.m.Y') ?></strong> 
                        со статусом "<?= $incompleteRecord->getStatusLabel() ?>".
                    </p>
                    <p>
                        Пожалуйста, сначала завершите заполнение данных за предыдущий день, 
                        а затем создавайте запись за текущий день.
                    </p>
                    <p>
                        <?= Html::a('<i class="fa fa-edit"></i> Заполнить данные за ' . Yii::$app->formatter->asDate($incompleteRecord->created_at, 'php:d.m.Y'), 
                            ['update', 'id' => $incompleteRecord->id], 
                            ['class' => 'btn btn-warning btn-sm']) ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-plus"></i> <?= Html::encode($this->title) ?>
                    </h3>
                </div>

                <div class="box-body">
                    <?php if ($incompleteRecord): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Форма временно заблокирована.</strong> 
                            Сначала завершите заполнение данных за предыдущий день.
                        </div>
                        <div style="opacity: 0.5; pointer-events: none;">
                            <?= $this->render('_form', [
                                'model' => $model,
                            ]) ?>
                        </div>
                    <?php else: ?>
                        <?= $this->render('_form', [
                            'model' => $model,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div> 