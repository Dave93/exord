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
                    'attribute' => 'product_id',
                    'label' => 'Продукт',
                    'value' => $model->product ? $model->product->name : '-',
                ],
                [
                    'attribute' => 'count',
                    'label' => 'Количество',
                    'value' => function ($model) {
                        $unit = $model->product ? $model->product->mainUnit : '';
                        return number_format($model->count, 2) . ' ' . $unit;
                    },
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
                    'attribute' => 'approved_count',
                    'label' => 'Утвержденное количество',
                    'value' => function ($model) {
                        if ($model->approved_count !== null) {
                            $unit = $model->product ? $model->product->mainUnit : '';
                            return number_format($model->approved_count, 2) . ' ' . $unit;
                        }
                        return '-';
                    },
                    'visible' => $model->status === ProductWriteoff::STATUS_APPROVED,
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

        <?php if ($isAdmin && $model->status === ProductWriteoff::STATUS_NEW): ?>
            <hr>
            <h4>Утверждение списания</h4>
            <?php $form = ActiveForm::begin([
                'action' => ['approve', 'id' => $model->id],
                'method' => 'post',
            ]); ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Утвержденное количество (оставьте пустым, чтобы использовать исходное количество)</label>
                        <input type="number" class="form-control" name="approved_count" step="any" min="0"
                               placeholder="<?= number_format($model->count, 2) ?>">
                    </div>
                </div>
            </div>
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
