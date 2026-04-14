<?php

/**
 * Database configuration — copy this file to db.php and configure your connection,
 * or use environment variables via a .env file (recommended).
 *
 * .env variables:
 *   DB_DSN=mysql:host=localhost;dbname=your_db
 *   DB_USERNAME=your_username
 *   DB_PASSWORD=your_password
 *   DB_CHARSET=utf8mb4
 */

return [
    'class' => 'yii\db\Connection',
    'dsn'      => $_ENV['DB_DSN']      ?? 'mysql:host=localhost;dbname=expense_manager',
    'username' => $_ENV['DB_USERNAME'] ?? 'your_username',
    'password' => $_ENV['DB_PASSWORD'] ?? 'your_password',
    'charset'  => $_ENV['DB_CHARSET']  ?? 'utf8mb4',

    // Schema cache options (for production environment)
    // 'enableSchemaCache' => true,
    // 'schemaCacheDuration' => 60,
    // 'schemaCache' => 'cache',
];
