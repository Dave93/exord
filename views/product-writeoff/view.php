<?php

use app\models\ProductWriteoff;
use app\models\User;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ProductWriteoff */

$this->title = 'Списание #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Списания', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$isAdmin = in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE]);
?>

<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?php if ($model->canEdit()): ?>
                <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-fill']) ?>
            <?php endif; ?>
            <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-default']) ?>
        </p>
    </div>
    <hr>
    <div class="content">
        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id',
                [
                    'attribute' => 'store_id',
                    'label' => 'Магазин',
                    'value' => $model->store ? $model->store->name : '-',
                ],
                [
                    'attribute' => 'created_by',
                    'label' => 'Создал',
                    'value' => $model->createdBy ? $model->createdBy->fullname : '-',
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'Дата создания',
                    'value' => date("d.m.Y H:i", strtotime($model->created_at)),
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Статус',
                    'value' => $model->getStatusLabel(),
                ],
                [
                    'attribute' => 'comment',
                    'label' => 'Комментарий',
                    'value' => $model->comment ?: '-',
                ],
                [
                    'attribute' => 'approved_by',
                    'label' => 'Утвердил',
                    'value' => $model->approvedBy ? $model->approvedBy->fullname : '-',
                    'visible' => $model->status === ProductWriteoff::STATUS_APPROVED,
                ],
                [
                    'attribute' => 'approved_at',
                    'label' => 'Дата утверждения',
                    'value' => $model->approved_at ? date("d.m.Y H:i", strtotime($model->approved_at)) : '-',
                    'visible' => $model->status === ProductWriteoff::STATUS_APPROVED,
                ],
            ],
        ]) ?>

        <h4>Позиции списания:</h4>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th width="50">№</th>
                    <th>Продукт</th>
                    <th class="text-center" width="100">Ед. изм.</th>
                    <th class="text-right" width="150">Количество</th>
                    <?php if ($model->status === ProductWriteoff::STATUS_APPROVED): ?>
                        <th class="text-right" width="150">Утверждено</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($model->items as $item): ?>
                    <tr>
                        <td class="text-center"><?= $i++ ?></td>
                        <td><?= Html::encode($item->product ? $item->product->name : '-') ?></td>
                        <td class="text-center"><?= Html::encode($item->product ? $item->product->mainUnit : '') ?></td>
                        <td class="text-right"><?= number_format($item->count, 2) ?></td>
                        <?php if ($model->status === ProductWriteoff::STATUS_APPROVED): ?>
                            <td class="text-right"><?= number_format($item->approved_count, 2) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($isAdmin && $model->status === ProductWriteoff::STATUS_NEW): ?>
            <hr>
            <h4>Утверждение списания</h4>
            <?php $form = ActiveForm::begin([
                'action' => ['approve', 'id' => $model->id],
                'method' => 'post',
            ]); ?>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Продукт</th>
                        <th class="text-center">Ед. изм.</th>
                        <th class="text-right">Заявлено</th>
                        <th width="200">Утверждено</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($model->items as $item): ?>
                        <tr>
                            <td><?= Html::encode($item->product ? $item->product->name : '-') ?></td>
                            <td class="text-center"><?= Html::encode($item->product ? $item->product->mainUnit : '') ?></td>
                            <td class="text-right"><?= number_format($item->count, 2) ?></td>
                            <td>
                                <input type="number" class="form-control"
                                       name="approved_counts[<?= $item->id ?>]"
                                       step="any"
                                       min="0"
                                       placeholder="<?= number_format($item->count, 2) ?>">
                                <small class="help-block">Оставьте пустым для утверждения заявленного</small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row">
                <div class="col-md-12">
                    <?= Html::submitButton('Утвердить списание', [
                        'class' => 'btn btn-success btn-fill',
                        'data' => [
                            'confirm' => 'Вы уверены, что хотите утвердить это списание?',
                        ],
                    ]) ?>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        <?php endif; ?>
    </div>
</div>
