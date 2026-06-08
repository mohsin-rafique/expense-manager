<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Web Application Configuration
 *
 * Main configuration file for the Expense Manager web application.
 * Defines application components, modules, and runtime settings.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

// Load external configuration files
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    |
    | Basic application identification settings including unique ID,
    | display name, and base path for file resolution.
    |
    */
    'id' => 'expense-manager',
    'name' => 'Expense Manager',
    'basePath' => dirname(__DIR__),
    'language' => 'en',
    'charset' => 'UTF-8',
    'timeZone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Path Aliases
    |--------------------------------------------------------------------------
    |
    | Define path aliases for commonly used directories.
    | These can be used throughout the application with @alias syntax.
    |
    */
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Components
    |--------------------------------------------------------------------------
    |
    | Components that should be instantiated during application bootstrap.
    | These run before the application handles the request.
    |
    */
    'bootstrap' => [
        'log',
        [
            'class' => 'app\components\LanguageSelector',
            'supportedLanguages' => ['en', 'es', 'fr', 'ur', 'de'],
        ],
        'app\components\CurrencyBootstrap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Components
    |--------------------------------------------------------------------------
    |
    | Core application components that provide essential functionality.
    | Components are lazy-loaded when first accessed via Yii::$app->componentName.
    |
    */
    'components' => [

        /*
        |----------------------------------------------------------------------
        | Request & Response
        |----------------------------------------------------------------------
        */
        'request' => [
            'cookieValidationKey' => 'qOmQVdlsOjhCypCYvNWcJZN1b-DQZbEX',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | User Authentication
        |----------------------------------------------------------------------
        */
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],

        /*
        |----------------------------------------------------------------------
        | Session Management
        |----------------------------------------------------------------------
        */
        'session' => [
            'class' => 'yii\web\Session',
            'name' => 'EXPENSE_MANAGER_SESSION',
            'cookieParams' => [
                'httpOnly' => true,
                'secure' => (bool)($_ENV['SESSION_SECURE'] ?? false),
                'sameSite' => $_ENV['SESSION_SAMESITE'] ?? 'Lax',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Error Handling
        |----------------------------------------------------------------------
        */
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        /*
        |----------------------------------------------------------------------
        | URL Management
        |----------------------------------------------------------------------
        |
        | Configure URL routing with pretty URLs, strict parsing, and
        | custom rewrite rules for all application controllers.
        |
        */
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                /*
                |------------------------------------------------------------------
                | REST API Routes
                |------------------------------------------------------------------
                */
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
                        'v1/expenses',
                        'v1/incomes',
                        'v1/user',
                    ],
                ],

                /*
                |------------------------------------------------------------------
                | Site Controller Routes
                |------------------------------------------------------------------
                */
                '' => 'site/index',                                    // Homepage
                'home' => 'site/index',                                // Alias for homepage
                'dashboard' => 'site/dashboard',                       // Dashboard
                'login' => 'site/login',                               // Login page
                'logout' => 'site/logout',                             // Logout action
                'signup' => 'site/signup',                             // Registration
                'register' => 'site/signup',                           // Alias for signup
                'contact' => 'site/contact',                           // Contact page
                'about' => 'site/about',                               // About page
                'captcha' => 'site/captcha',                           // Captcha action
                'request-password-reset' => 'site/request-password-reset',  // Forgot password
                'forgot-password' => 'site/request-password-reset',    // Alias
                'reset-password/<token:[\w\-]+>' => 'site/reset-password',  // Reset with token
                'verify-email/<token:[\w\-]+>' => 'site/verify-email', // Email verification
                'resend-verification-email' => 'site/resend-verification-email',

                /*
                |------------------------------------------------------------------
                | Profile Controller Routes
                |------------------------------------------------------------------
                */
                'profile' => 'profile/index',                          // View profile
                'profile/view' => 'profile/index',                     // Alias
                'profile/edit' => 'profile/update',                    // Edit profile
                'profile/update' => 'profile/update',                  // Update profile
                'profile/settings' => 'profile/settings',              // Settings page
                'profile/settings/<tab:[\w\-]+>' => 'profile/settings', // Settings with tab
                'profile/change-password' => 'profile/change-password', // Change password
                'profile/upload-avatar' => 'profile/upload-avatar',    // Avatar upload
                'profile/delete-avatar' => 'profile/delete-avatar',    // Avatar delete
                'profile/upload-banner' => 'profile/upload-banner',    // Banner upload
                'profile/delete-banner' => 'profile/delete-banner',    // Banner delete
                'profile/export' => 'profile/export',                  // Export data
                'profile/<action:[\w\-]+>' => 'profile/<action>',      // Catch-all for profile

                /*
                |------------------------------------------------------------------
                | Income Controller Routes
                |------------------------------------------------------------------
                */
                'income' => 'income/index',                            // Income list
                'incomes' => 'income/index',                           // Alias (plural)
                'income/list' => 'income/index',                       // Alias
                'income/create' => 'income/create',                    // Create new income
                'income/add' => 'income/create',                       // Alias
                'income/new' => 'income/create',                       // Alias
                'income/<id:\d+>' => 'income/view',                    // View single income
                'income/view/<id:\d+>' => 'income/view',               // Explicit view
                'income/edit/<id:\d+>' => 'income/update',             // Edit income
                'income/update/<id:\d+>' => 'income/update',           // Update income
                'income/delete/<id:\d+>' => 'income/delete',           // Delete income
                'income/export' => 'income/export',                    // Export incomes
                ['pattern' => 'income/import', 'route' => 'import/index', 'defaults' => ['type' => 'income']], // Import incomes
                'income/report' => 'income/report',                    // Income report
                'income/summary' => 'income/summary',                  // Income summary
                'income/chart-data' => 'income/chart-data',            // Chart data (AJAX)
                'income/bulk-delete' => 'income/bulk-delete',          // Bulk delete
                'income/<action:[\w\-]+>' => 'income/<action>',        // Catch-all for income

                /*
                |------------------------------------------------------------------
                | Income Category Routes
                |------------------------------------------------------------------
                */
                'income-categories' => 'income-category/index',        // Category list
                'income-category' => 'income-category/index',          // Alias (singular)
                'income-category/create' => 'income-category/create',  // Create category
                'income-category/add' => 'income-category/create',     // Alias
                'income-category/<id:\d+>' => 'income-category/view',  // View category
                'income-category/edit/<id:\d+>' => 'income-category/update',  // Edit category
                'income-category/update/<id:\d+>' => 'income-category/update', // Update
                'income-category/delete/<id:\d+>' => 'income-category/delete', // Delete
                'income-category/<action:[\w\-]+>' => 'income-category/<action>', // Catch-all

                /*
                |------------------------------------------------------------------
                | Expense Controller Routes
                |------------------------------------------------------------------
                */
                'expense' => 'expense/index',                          // Expense list
                'expenses' => 'expense/index',                         // Alias (plural)
                'expense/list' => 'expense/index',                     // Alias
                'expense/create' => 'expense/create',                  // Create new expense
                'expense/add' => 'expense/create',                     // Alias
                'expense/new' => 'expense/create',                     // Alias
                'expense/<id:\d+>' => 'expense/view',                  // View single expense
                'expense/view/<id:\d+>' => 'expense/view',             // Explicit view
                'expense/edit/<id:\d+>' => 'expense/update',           // Edit expense
                'expense/update/<id:\d+>' => 'expense/update',         // Update expense
                'expense/delete/<id:\d+>' => 'expense/delete',         // Delete expense
                'expense/export' => 'expense/export',                  // Export expenses
                ['pattern' => 'expense/import', 'route' => 'import/index', 'defaults' => ['type' => 'expense']], // Import expenses
                'expense/report' => 'expense/report',                  // Expense report
                'expense/summary' => 'expense/summary',                // Expense summary
                'expense/chart-data' => 'expense/chart-data',          // Chart data (AJAX)
                'expense/bulk-delete' => 'expense/bulk-delete',        // Bulk delete
                'expense/fiscal-year' => 'expense/fiscal-year',        // Fiscal year view
                'expense/fiscal-year/<year:[\w\-]+>' => 'expense/fiscal-year', // Specific FY
                'expense/<action:[\w\-]+>' => 'expense/<action>',      // Catch-all for expense

                /*
                |------------------------------------------------------------------
                | Expense Category Routes
                |------------------------------------------------------------------
                */
                'expense-categories' => 'expense-category/index',      // Category list
                'expense-category' => 'expense-category/index',        // Alias (singular)
                'expense-category/create' => 'expense-category/create', // Create category
                'expense-category/add' => 'expense-category/create',   // Alias
                'expense-category/<id:\d+>' => 'expense-category/view', // View category
                'expense-category/edit/<id:\d+>' => 'expense-category/update', // Edit
                'expense-category/update/<id:\d+>' => 'expense-category/update', // Update
                'expense-category/delete/<id:\d+>' => 'expense-category/delete', // Delete
                'expense-category/<action:[\w\-]+>' => 'expense-category/<action>', // Catch-all

                /*
                |------------------------------------------------------------------
                | Settings Routes
                |------------------------------------------------------------------
                */
                'settings' => 'settings/index',                        // Settings page
                'settings/general' => 'settings/general',              // General settings
                'settings/currency' => 'settings/currency',            // Currency settings
                'settings/appearance' => 'settings/appearance',        // Appearance
                'settings/notifications' => 'settings/notifications',  // Notifications
                'settings/backup' => 'settings/backup',                // Backup settings
                'settings/export-database' => 'settings/export-database', // DB export
                'settings/<action:[\w\-]+>' => 'settings/<action>',    // Catch-all

                /*
                |------------------------------------------------------------------
                | Budget Routes
                |------------------------------------------------------------------
                */
                'budgets' => 'budget/index',                           // Budget list
                'budget' => 'budget/index',                            // Alias (singular)
                'budget/create' => 'budget/create',                    // Create budget
                'budget/add' => 'budget/create',                       // Alias
                'budget/<id:\d+>' => 'budget/view',                    // View budget
                'budget/edit/<id:\d+>' => 'budget/update',             // Edit budget
                'budget/update/<id:\d+>' => 'budget/update',           // Update budget
                'budget/delete/<id:\d+>' => 'budget/delete',           // Delete budget
                'budget/<action:[\w\-]+>' => 'budget/<action>',        // Catch-all

                /*
                |------------------------------------------------------------------
                | Import Routes
                |------------------------------------------------------------------
                */
                'import' => 'import/index',                             // Import wizard
                'import/template' => 'import/template',                 // Download template
                'import/<action:[\w\-]+>' => 'import/<action>',         // Catch-all

                /*
                |------------------------------------------------------------------
                | Report Routes
                |------------------------------------------------------------------
                */
                'reports' => 'report/index',                           // Reports dashboard
                'report' => 'report/index',                            // Alias
                'report/monthly' => 'report/monthly',                  // Monthly report
                'report/monthly/<year:\d{4}>/<month:\d{1,2}>' => 'report/monthly', // Specific month
                'report/yearly' => 'report/yearly',                    // Yearly report
                'report/yearly/<year:\d{4}>' => 'report/yearly',       // Specific year
                'report/fiscal-year' => 'report/fiscal-year',          // Fiscal year report
                'report/fiscal-year/<year:[\w\-]+>' => 'report/fiscal-year', // Specific FY
                'report/category' => 'report/category',                // Category report
                'report/comparison' => 'report/comparison',            // Comparison report
                'report/export/<type:[\w\-]+>' => 'report/export',     // Export report
                'report/<action:[\w\-]+>' => 'report/<action>',        // Catch-all

                /*
                |------------------------------------------------------------------
                | Generic Fallback Routes
                |------------------------------------------------------------------
                | These catch-all rules should be placed at the end.
                |
                */
                '<controller:\w+>' => '<controller>/index',
                '<controller:\w+>/<action:[\w\-]+>' => '<controller>/<action>',
                '<controller:\w+>/<action:[\w\-]+>/<id:\d+>' => '<controller>/<action>',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Database Connection
        |----------------------------------------------------------------------
        */
        'db' => $db,

        /*
        |----------------------------------------------------------------------
        | Caching
        |----------------------------------------------------------------------
        */
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache',
        ],

        /*
        |----------------------------------------------------------------------
        | Logging
        |----------------------------------------------------------------------
        |
        | Log targets for different severity levels.
        | Each level writes to its own file for easier debugging and monitoring.
        |
        | Log Levels (by severity):
        | - error   : Runtime errors that need immediate attention
        | - warning : Exceptional occurrences that are not errors
        | - info    : Interesting events (user logins, SQL queries)
        | - trace   : Detailed debug information
        | - profile : Profiling messages for performance analysis
        |
        | Usage in code:
        | Yii::error('Payment failed', 'app\payments');
        | Yii::warning('Low stock', 'app\inventory');
        | Yii::info('User logged in', 'app\auth');
        |
        */
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 100,
            'targets' => [

                /*
                 * Error Log
                 * Critical issues requiring immediate attention.
                 * Excludes 404 errors to reduce noise.
                 */
                'error' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                    'logFile' => '@runtime/logs/error.log',
                    'maxFileSize' => 10240, // 10MB
                    'maxLogFiles' => 10,
                    'logVars' => ['_GET', '_POST', '_SESSION', '_COOKIE'],
                    'except' => [
                        'yii\web\HttpException:404',
                    ],
                ],

                /*
                 * Warning Log
                 * Non-critical issues that should be monitored.
                 */
                'warning' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['warning'],
                    'logFile' => '@runtime/logs/warning.log',
                    'maxFileSize' => 10240, // 10MB
                    'maxLogFiles' => 5,
                    'logVars' => ['_GET', '_POST'],
                ],

                /*
                 * Info Log
                 * General application events and user activities.
                 * Excludes database queries to keep log clean.
                 */
                'info' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'logFile' => '@runtime/logs/info.log',
                    'maxFileSize' => 10240, // 10MB
                    'maxLogFiles' => 5,
                    'logVars' => [],
                    'except' => [
                        'yii\db\*',
                    ],
                ],

                /*
                 * Application Log
                 * Combined log for all levels - useful for quick review.
                 */
                'app' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@runtime/logs/app.log',
                    'maxFileSize' => 10240, // 10MB
                    'maxLogFiles' => 5,
                    'logVars' => [],
                ],

                /*
                 * SQL Query Log (Development Only)
                 * Database queries for debugging and optimization.
                 */
                'sql' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'trace'],
                    'categories' => ['yii\db\*'],
                    'logFile' => '@runtime/logs/sql.log',
                    'maxFileSize' => 10240, // 10MB
                    'maxLogFiles' => 3,
                    'logVars' => [],
                    'enabled' => YII_DEBUG,
                ],

                /*
                 * Security Log
                 * Authentication attempts, permission issues, security events.
                 */
                'security' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'warning', 'error'],
                    'categories' => [
                        'yii\web\User::*',
                        'app\models\User::*',
                        'app\models\LoginForm::*',
                    ],
                    'logFile' => '@runtime/logs/security.log',
                    'maxFileSize' => 10240, // 10MB
                    'maxLogFiles' => 10,
                    'logVars' => ['_GET', '_POST', '_SERVER'],
                ],

            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Mailer
        |----------------------------------------------------------------------
        */
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true, // Set to false in production
            // 'transport' => [
            //     'scheme' => 'smtps',
            //     'host' => 'smtp.example.com',
            //     'username' => 'username',
            //     'password' => 'password',
            //     'port' => 465,
            // ],
        ],

        /*
        |----------------------------------------------------------------------
        | Internationalization & Formatting
        |----------------------------------------------------------------------
        */
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ],

        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'currencyCode' => 'USD',
            'thousandSeparator' => ',',
            'decimalSeparator' => '.',
            'dateFormat' => 'php:M d, Y',
            'datetimeFormat' => 'php:M d, Y H:i',
            'nullDisplay' => '—',
        ],

        /*
        |----------------------------------------------------------------------
        | Custom Components
        |----------------------------------------------------------------------
        |
        | Application-specific components for business logic.
        |
        */

        /**
         * Currency Formatter
         *
         * Provides comprehensive currency formatting with:
         * - Custom symbol positions (left, right, with/without space)
         * - User-configurable decimal places and separators
         * - Compact notation for dashboards (k, M, B)
         *
         * @see app\components\CurrencyFormatter
         * @usage Yii::$app->currency->format(1234.56)
         */
        'currency' => [
            'class' => 'app\components\CurrencyFormatter',
            'currencyCode' => 'USD',
            'symbolPosition' => 'left',
            'decimalPlaces' => 2,
            'thousandSeparator' => ',',
            'decimalSeparator' => '.',
        ],

        /**
         * Balance Helper
         *
         * Provides balance calculation utilities for dashboard
         * and financial reporting features.
         *
         * @see app\components\BalanceHelper
         * @usage Yii::$app->balanceHelper->getBalance()
         */
        'balanceHelper' => [
            'class' => 'app\components\BalanceHelper',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Application Parameters
    |--------------------------------------------------------------------------
    |
    | Custom application parameters accessible via Yii::$app->params['key'].
    | Defined in config/params.php for easy management.
    |
    */
    'params' => $params,
];

/*
|--------------------------------------------------------------------------
| Development Environment Configuration
|--------------------------------------------------------------------------
|
| Additional modules and settings for development environment only.
| These are automatically disabled in production.
|
*/
if (YII_ENV_DEV) {
    // Debug Toolbar
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    // Gii Code Generator
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
