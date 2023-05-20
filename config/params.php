<?php
Yii::setAlias('@web', realpath(dirname(__FILE__) . '/../web/'));
Yii::setAlias('@uploads', realpath(dirname(__FILE__) . '/../web/uploads/'));

return [
    'adminEmail' => 'admin@example.com',
];
