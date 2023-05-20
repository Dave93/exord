<?php

/* @var $this yii\web\View */

$this->title = 'Заказы на 09.08.2018';
$establishments = \app\models\Establishments::find()->orderBy(['name' => SORT_ASC])->asArray()->all();
$categories = \app\models\Categories::find()->orderBy(['name' => SORT_ASC])->asArray()->all();
?>
<div class="page-header clearfix">
    <h1><?= $this->title ?></h1>
</div>
<div class="row">
    <div class="col-md-3 col-lg-2">
        <h4 class="tab-block-title">Филиалы</h4>
        <ul class="nav nav-tabs tabs-left">
            <?php $i = 0; ?>
            <?php foreach ($establishments as $establishment): ?>
                <li class="<?= ($i == 0) ? 'active' : '' ?>"><a href="#est-<?= $establishment['id'] ?>"
                                                                data-toggle="tab"><?= $establishment['name'] ?></a></li>
                <?php $i++; ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="col-md-9 col-lg-10">
        <h4 class="tab-block-title">Продукты</h4>
        <div class="tab-content">
            <?php $i = 0; ?>
            <?php foreach ($establishments as $establishment): ?>
                <div class="tab-pane <?= ($i == 0) ? 'active' : '' ?>" id="est-<?= $establishment['id'] ?>">
                    <table class="table table-hover order-table">
                        <thead>
                        <tr>
                            <th>Наименование</th>
                            <th>Ед. изм.</th>
                            <th>Количество</th>
                            <th>Факт склад</th>
                            <th>Факт закуп</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td colspan="5" class="bold table-title"><?= $category['name'] ?></td>
                            </tr>
                            <?php $products = \app\models\Products::find()->where(['category_id' => $category['id']])->orderBy(['name' => SORT_ASC])->asArray()->all() ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= $product['name'] ?></td>
                                    <td><?= $product['measureUnit'] ?></td>
                                    <td><?=\yii\helpers\Html::textInput('quantity',null,['class'=>'form-control'])?></td>
                                    <td><?=\yii\helpers\Html::textInput('fact_storage',null,['class'=>'form-control'])?></td>
                                    <td><?=\yii\helpers\Html::textInput('fact_buy',null,['class'=>'form-control'])?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
                <?php $i++; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
