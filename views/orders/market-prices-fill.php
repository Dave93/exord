<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Orders */
/* @var $items array */

$this->title = "Цены базара — заказ #{$model->id}";
$this->params['breadcrumbs'][] = ['label' => 'Заполнение цен базара', 'url' => ['orders/market-prices']];
$this->params['breadcrumbs'][] = "#{$model->id}";
?>
<div class="card">
    <div class="header clearfix">
        <h2 class="pull-left title"><?= Html::encode($this->title) ?></h2>
        <p class="pull-right">
            <?= Html::a('Назад', ['orders/market-prices'], ['class' => 'btn btn-primary btn-fill']) ?>
        </p>
    </div>
    <hr>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <div class="content">
        <dl class="dl-horizontal">
            <dt>Филиал</dt>
            <dd><?= Html::encode($model->store ? $model->store->name : '-') ?></dd>
            <dt>Дата</dt>
            <dd><?= Html::encode(date('d.m.Y', strtotime($model->date))) ?></dd>
            <dt>Комментарий</dt>
            <dd><?= nl2br(Html::encode($model->comment ?: '-')) ?></dd>
        </dl>

        <?php if (empty($items)): ?>
            <div class="alert alert-warning">В заказе нет базарных позиций.</div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

                <table class="table table-hover" id="market-prices-table">
                    <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Ед. изм.</th>
                            <th class="text-right">Кол-во заказа</th>
                            <th class="text-right">Факт приёма</th>
                            <th class="text-right" style="width: 180px;">Сумма за позицию</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $total = 0;
                    foreach ($items as $row):
                        $fact = $row['factStoreQuantity'];
                        if ($fact === null || $fact === '' || (float)$fact == 0.0) {
                            $fact = $row['factSupplierQuantity'];
                        }
                        $price = $row['market_total_price'];
                        if ($price !== null && $price !== '') {
                            $total += (float)$price;
                        }
                    ?>
                        <tr>
                            <td><?= Html::encode($row['name']) ?></td>
                            <td><?= Html::encode($row['mainUnit']) ?></td>
                            <td class="text-right"><?= Html::encode($row['quantity']) ?></td>
                            <td class="text-right"><?= Html::encode($fact) ?></td>
                            <td class="text-right">
                                <input type="number" step="0.01" min="0"
                                       class="form-control market-price-input"
                                       name="Prices[<?= Html::encode($row['productId']) ?>]"
                                       value="<?= $price !== null ? Html::encode((string)(float)$price) : '' ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Итого:</th>
                            <th class="text-right"><span id="market-prices-total"><?= number_format($total, 2, '.', ' ') ?></span></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="form-group">
                    <button type="submit" name="action" value="save" class="btn btn-default btn-fill">Сохранить</button>
                    <button type="submit" name="action" value="finish" class="btn btn-success btn-fill">Сохранить и завершить</button>
                </div>
            </form>

            <script>
                (function () {
                    var inputs = document.querySelectorAll('.market-price-input');
                    var totalEl = document.getElementById('market-prices-total');
                    function recalc() {
                        var sum = 0;
                        inputs.forEach(function (el) {
                            var v = parseFloat(el.value);
                            if (!isNaN(v)) sum += v;
                        });
                        totalEl.textContent = sum.toFixed(2);
                    }
                    inputs.forEach(function (el) { el.addEventListener('input', recalc); });
                })();
            </script>
        <?php endif; ?>
    </div>
</div>
