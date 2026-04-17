<?php

use app\models\MealOrders;
use app\models\User;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $order array */
/* @var $items app\models\MealOrderItems[] */
/* @var $active string */

$i = 0;
$stateLabel = isset(MealOrders::$states[$order['state']]) ? MealOrders::$states[$order['state']] : '';
?>
<div class="tab-pane <?= $active ?>" id="st-<?= $order['id'] ?>">
    <?php $form = ActiveForm::begin(); ?>
    <div class="flex justify-between">
        <?php if (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_STOCK, User::ROLE_DISH_COOK]) && $order['state'] == 0): ?>
            <?= Html::submitButton("Отправить", ['class' => 'btn btn-success btn-fill', 'name' => 'send', 'value' => 'Y']) ?>
        <?php endif; ?>
    </div>
    <input type="hidden" name="orderId" value="<?= $order['id'] ?>">

    <table class="table table-hover table-striped order-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Наименование</th>
            <th>Ед. изм.</th>
            <th class="text-center">Заказано</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php $i++; ?>
            <tr>
                <td><?= $i ?></td>
                <td><?= $item->dish ? Html::encode($item->dish->name) : '-' ?></td>
                <td><?= $item->dish ? Html::encode($item->dish->unit) : '-' ?></td>
                <td class="text-center">
                    <?php if (Yii::$app->user->identity->role == User::ROLE_ADMIN): ?>
                        <input type="number" step="0.001" min="0"
                               name="Items[<?= $item->dishId ?>]"
                               value="<?= $item->quantity ?>"
                               class="form-control text-center"
                               style="width: 110px; margin: 0 auto;">
                    <?php else: ?>
                        <?= $item->quantity ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>
    <h3 class="title">Комментарий</h3>
    <textarea name="comment" type="text" class="form-control"><?= Html::encode($order['comment']) ?></textarea>
    <hr>
    <div class="form-group text-right flex justify-between mt-20">
        <?php if (Yii::$app->user->identity->role == User::ROLE_ADMIN): ?>
            <?= Html::submitButton("Удалить", ['class' => 'btn btn-danger btn-fill', 'name' => 'delete', 'value' => 'Y', 'data-confirm' => 'Вы действительно хотите удалить заказ блюд #' . $order['id'] . '?']) ?>
        <?php endif; ?>

        <?php if (in_array(Yii::$app->user->identity->role, [User::ROLE_ADMIN, User::ROLE_OFFICE])): ?>
            <?= Html::button('<i class="glyphicon glyphicon-time"></i> История изменений', [
                'class' => 'btn btn-info btn-fill show-changelog-modal',
                'data-order-id' => $order['id']
            ]) ?>
        <?php endif; ?>

        <div>
            <?= Html::submitButton("Сохранить", ['class' => 'btn btn-primary mr-5 btn-fill', 'name' => 'save', 'value' => 'Y']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
