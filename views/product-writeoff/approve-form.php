<?php

use app\models\ProductWriteoff;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProductWriteoff */

$this->title = 'Подтверждение списания #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Управление списаниями', 'url' => ['admin-index']];
$this->params['breadcrumbs'][] = ['label' => 'Списание #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Подтверждение';
?>

<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад к списанию', ['view', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
            <?= Html::a('К списку', ['admin-index'], ['class' => 'btn btn-default']) ?>
        </p>
    </div>
    <hr>
    <div class="content">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Магазин:</strong> <?= Html::encode($model->store ? $model->store->name : '-') ?></p>
                <p><strong>Создал:</strong> <?= Html::encode($model->createdBy ? $model->createdBy->fullname : '-') ?></p>
                <p><strong>Дата создания:</strong> <?= date("d.m.Y H:i", strtotime($model->created_at)) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Статус:</strong> <?= $model->getStatusLabel() ?></p>
                <p><strong>Количество позиций:</strong> <?= $model->getItemsCount() ?></p>
                <?php if ($model->comment): ?>
                    <p><strong>Комментарий:</strong> <?= nl2br(Html::encode($model->comment)) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <h4>Подтверждение количества</h4>
        <p class="text-muted">
            По умолчанию будет утверждено заявленное количество. Измените значения, если необходимо скорректировать количество.
        </p>

        <?php $form = ActiveForm::begin([
            'action' => ['approve', 'id' => $model->id],
            'method' => 'post',
        ]); ?>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th width="50">№</th>
                    <th>Продукт</th>
                    <th class="text-center" width="100">Ед. изм.</th>
                    <th class="text-right" width="150">Заявлено</th>
                    <th width="200">Подтверждаемое количество</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($model->items as $item): ?>
                    <tr>
                        <td class="text-center"><?= $i++ ?></td>
                        <td><?= Html::encode($item->product ? $item->product->name : '-') ?></td>
                        <td class="text-center"><?= Html::encode($item->product ? $item->product->mainUnit : '') ?></td>
                        <td class="text-right"><?= number_format($item->count, 2) ?></td>
                        <td>
                            <input type="number"
                                   class="form-control"
                                   name="approved_counts[<?= $item->id ?>]"
                                   step="any"
                                   min="0"
                                   value="<?= $item->count ?>"
                                   placeholder="<?= number_format($item->count, 2) ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($model->photos)): ?>
            <hr>
            <h4>Прикрепленные фотографии:</h4>
            <div style="margin-top: 15px;">
                <?php foreach ($model->photos as $photo): ?>
                    <div style="display: inline-block; margin-right: 10px; margin-bottom: 10px;">
                        <a href="<?= $photo->getFileUrl() ?>" target="_blank">
                            <img src="<?= $photo->getFileUrl() ?>"
                                 alt="Фото"
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <hr>

        <div class="row">
            <div class="col-md-12">
                <?= Html::submitButton('Утвердить списание', [
                    'class' => 'btn btn-success btn-fill btn-lg',
                    'data' => [
                        'confirm' => 'Вы уверены, что хотите утвердить это списание с указанными количествами?',
                    ],
                ]) ?>
                <?= Html::a('Отмена', ['admin-index'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
