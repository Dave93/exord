<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=les_exord_db',
    'username' => 'root',
    'password' => 'LesAiles@2019',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 90 * 24 * 3600,
    'schemaCache' => 'cache',
];
