<?php

use app\models\Products;
use app\models\Zone;
use kartik\date\DatePicker;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ProductSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $users array */

$this->title = 'Привязка товаров';
$this->params['breadcrumbs'][] = $this->title;

?>


<table class="table table-bordered table-responsive table-striped table-sticky-header">
    <thead>
        <tr>
            <th>Продукт</th>
            <th>Категория</th>
            <?php foreach ($users as $user) {?>
                <th><?=$user['fullname']?></th>
            <?php }?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product) {?>
            <tr>
                <td><?=$product['name']?></td>
                <td><?=$product['category']?></td>
                <?php foreach ($product['users'] as $userId => $user) {?>
                    <td>
                        <input type="checkbox" name="user[<?=$userId?>][product][<?=$product['id']?>]" data-product="<?=$product['id']?>" data-user="<?=$userId?>" value="<?=$product['id']?>" <?php echo $user == 1 ? 'checked' : '';?> />
                    </td>
                <?php }?>
            </tr>
        <?php }?>
    </tbody>
</table>

<script>
    $(document).on('click', '[data-user]', function(){
        console.log($(this).is(':checked'));
        var user = $(this).data('user');
        var product = $(this).data('product');
        var checked = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            url: '/products/links',
            type: 'post',
            data: {
                user: user,
                product: product,
                checked: checked
            },
            success: function(data) {
                console.log(data);
            }
        });
    });
</script>