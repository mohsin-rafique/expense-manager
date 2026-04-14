<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Error View
 *
 * Displays user-friendly error messages for HTTP errors.
 * Provides clear guidance and navigation options.
 *
 * @var yii\web\View $this
 * @var string $name Error name (e.g., "Not Found")
 * @var string $message Error message
 * @var Exception $exception The exception object
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;

// Set the error layout
$this->context->layout = 'error';

// Extract error code from exception
$code = $exception instanceof \yii\web\HttpException ? $exception->statusCode : 500;

$this->title = Yii::t('app', 'Error {code}', ['code' => $code]);

// Error messages by code
$errorMessages = [
    400 => [
        'title' => Yii::t('app', 'Bad Request'),
        'message' => Yii::t('app', 'The server could not understand your request. Please check your input and try again.'),
    ],
    401 => [
        'title' => Yii::t('app', 'Unauthorized'),
        'message' => Yii::t('app', 'You need to sign in to access this page. Please log in with your credentials.'),
    ],
    403 => [
        'title' => Yii::t('app', 'Access Denied'),
        'message' => Yii::t('app', 'You don\'t have permission to access this resource. Contact support if you believe this is an error.'),
    ],
    404 => [
        'title' => Yii::t('app', 'Page Not Found'),
        'message' => Yii::t('app', 'The page you\'re looking for doesn\'t exist or has been moved. Let\'s get you back on track.'),
    ],
    405 => [
        'title' => Yii::t('app', 'Method Not Allowed'),
        'message' => Yii::t('app', 'The requested method is not supported for this resource. Please try a different approach.'),
    ],
    500 => [
        'title' => Yii::t('app', 'Server Error'),
        'message' => Yii::t('app', 'Something went wrong on our end. Our team has been notified and is working to fix it.'),
    ],
    502 => [
        'title' => Yii::t('app', 'Bad Gateway'),
        'message' => Yii::t('app', 'We\'re experiencing temporary connectivity issues. Please try again in a few moments.'),
    ],
    503 => [
        'title' => Yii::t('app', 'Service Unavailable'),
        'message' => Yii::t('app', 'We\'re currently performing maintenance. Please check back shortly.'),
    ],
];

// Get appropriate message or use defaults
$errorInfo = $errorMessages[$code] ?? [
    'title' => $name,
    'message' => $message,
];
?>

<!-- ============================================================== -->
<!-- Error Page Content                                             -->
<!-- ============================================================== -->
<div class="error-page">

    <!-- Error Illustration -->
    <div class="error-illustration">
        <?php if ($code === 404): ?>
            <!-- Search/Lost Icon for 404 -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        <?php elseif ($code === 403): ?>
            <!-- Lock Icon for 403 -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        <?php elseif ($code >= 500): ?>
            <!-- Server Error Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        <?php else: ?>
            <!-- Generic Error Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        <?php endif ?>
    </div>

    <!-- Error Code -->
    <div class="error-code"><?= $code ?></div>

    <!-- Error Title -->
    <h1 class="error-title"><?= Html::encode($errorInfo['title']) ?></h1>

    <!-- Error Message -->
    <p class="error-message"><?= Html::encode($errorInfo['message']) ?></p>

    <!-- Action Buttons -->
    <div class="error-actions">
        <?= Html::a(
            Yii::t('app', 'Go to Homepage'),
            Yii::$app->homeUrl,
            ['class' => 'btn btn-primary']
        ) ?>

        <?= Html::button(
            Yii::t('app', 'Go Back'),
            [
                'class' => 'btn btn-outline-secondary',
                'onclick' => 'history.back(); return false;',
            ]
        ) ?>

        <?php if ($code === 404): ?>
            <?= Html::a(
                Yii::t('app', 'Contact Support'),
                ['/site/contact'],
                ['class' => 'btn btn-outline-primary']
            ) ?>
        <?php endif ?>
    </div>

    <!-- Debug Information (only in development) -->
    <?php if (YII_DEBUG): ?>
        <details class="error-details">
            <summary><?= Yii::t('app', 'Technical Details') ?></summary>
            <pre><?= Html::encode($exception->getMessage()) ?>
            File: <?= Html::encode($exception->getFile()) ?>
            Line: <?= Html::encode($exception->getLine()) ?></pre>
        </details>
    <?php endif ?>

    <!-- Footer -->
    <div class="error-footer">
        <p>
            <?= Html::a(Html::encode(Yii::$app->name), Yii::$app->homeUrl) ?>
            &middot;
            <?= Yii::t('app', 'Need help?') ?>
            <?= Html::a(Yii::t('app', 'Contact us'), ['/site/contact']) ?>
        </p>
    </div>

</div>
<!-- End Error Page -->
