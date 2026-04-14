<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Authentication Layout Template
 *
 * Minimal layout for authentication pages including:
 * - Login
 * - Sign Up / Register
 * - Forgot Password
 * - Reset Password / Verify Password
 *
 * Features a two-column card design:
 * - Left: Dynamic content (forms)
 * - Right: Promotional branding panel
 *
 * ## Usage
 *
 * ```php
 * // In controller
 * $this->layout = 'auth';
 * ```
 *
 * ## View Parameters
 *
 * Views can customize the promo panel via $this->params:
 * - authPromoTitle: Custom promo heading
 * - authPromoText: Custom promo description
 * - authPromoButtonText: Custom button text
 * - authPromoButtonUrl: Custom button URL
 * - authPromoFeatures: Array of feature strings
 *
 * @var yii\web\View $this The view object
 * @var string $content The main content rendered by the controller
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use app\assets\AppAsset;
use yii\bootstrap5\Html;
use yii\helpers\Url;

AppAsset::register($this);

// Register meta tags
$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? 'Expense Manager - Sign in to manage your finances']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? 'login, signin, expense manager']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);
$this->registerMetaTag(['name' => 'theme-color', 'content' => '#2563eb']);

// Register favicon and icons
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/svg+xml', 'href' => Yii::getAlias('@web/favicon.svg')]);
$this->registerLinkTag(['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => Yii::getAlias('@web/apple-touch-icon.png')]);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/png', 'sizes' => '32x32', 'href' => Yii::getAlias('@web/favicon-32.png')]);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/png', 'sizes' => '16x16', 'href' => Yii::getAlias('@web/favicon-16.png')]);
$this->registerLinkTag(['rel' => 'manifest', 'href' => Yii::getAlias('@web/site.webmanifest')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">

<head>
    <title><?= Html::encode($this->title) ?> | <?= Html::encode(Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>

<body class="bg-body-tertiary">
    <?php $this->beginBody() ?>

    <!-- ============================================================== -->
    <!-- Authentication Container                                       -->
    <!-- ============================================================== -->
    <div class="min-vh-100 d-flex flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">

                    <!-- Auth Card Group -->
                    <div class="card-group d-block d-md-flex row shadow-lg">

                        <!-- Left: Form Content -->
                        <div class="card col-md-7 p-4 mb-0 border-0">
                            <div class="card-body">
                                <?= $content ?>
                            </div>
                        </div>

                        <!-- Right: Promotional Panel -->
                        <?= $this->render('_auth_promo') ?>

                    </div>

                    <!-- Footer -->
                    <?= $this->render('_auth_footer') ?>

                </div>
            </div>
        </div>
    </div>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>
