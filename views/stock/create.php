<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Stock */

$this->title = 'Добавить';
$this->params['breadcrumbs'][] = ['label' => 'Приходы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="orders-list">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-4 col-lg-3">
            <h4 class="tab-block-title">Категории</h4>
            <ul class="nav nav-tabs tabs-left">
                <?php $i = 0; ?>
                <?php foreach ($categories as $category): ?>
                    <li class="<?= ($i == 0) ? 'active' : '' ?>">
                        <a href="#est-<?= $category['id'] ?>" data-toggle="tab"><?= $category['name'] ?></a></li>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-8 col-lg-9">
            <h4 class="tab-block-title">Продукты</h4>
            <div class="tab-content">
                <?php $i = 0; ?>
                <?php foreach ($categories as $category): ?>
                    <?php $items = \app\models\Products::find()->where(['category_id' => $category['id']])->asArray()->all(); ?>
                    <div class="tab-pane <?= ($i == 0) ? 'active' : '' ?>" id="est-<?= $category['id'] ?>">
                        <table class="table table-hover order-table">
                            <thead>
                            <tr>
                                <th>Наименование</th>
                                <th>Ед. изм.</th>
                                <th>Количество</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= $item['name'] ?></td>
                                    <td><?= $item['measureUnit'] ?></td>
                                    <td width="200">
                                        <?= Html::hiddenInput("Items[{$i}][id]", $item['id']) ?>
                                        <?= Html::textInput("Items[{$i}][amount]", null, ['class' => 'form-control']) ?>
                                    </td>
                                </tr>
                                <?php $i++; ?>
                            <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>