<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Main Layout Template
 *
 * This is the primary layout file for the Expense Manager application.
 * Uses a modular structure with subviews for header, navigation, footer, and modals.
 *
 * ## Layout Structure
 *
 * ```
 * ┌─────────────────────────────────────┐
 * │     Header (meta, assets, title)    │
 * ├─────────────────────────────────────┤
 * │        Navigation Bar (_navbar)     │
 * ├─────────────────────────────────────┤
 * │                                     │
 * │         Main Content Area           │
 * │         (Flash Messages)            │
 * │         (Page Content)              │
 * │                                     │
 * ├─────────────────────────────────────┤
 * │          Footer (_footer)           │
 * ├─────────────────────────────────────┤
 * │          Modals (_modals)           │
 * └─────────────────────────────────────┘
 * ```
 *
 * ## Subviews
 * - _navbar.php: Main navigation bar
 * - _footer.php: Page footer
 * - _modals.php: Global modals (AJAX, delete confirmation)
 * - _scripts.php: Global JavaScript utilities
 *
 * @var yii\web\View $this The view object
 * @var string $content The main content rendered by the controller
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Html;

// Register assets
AppAsset::register($this);

// Register meta tags
$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? 'Expense Manager - Track your income and expenses']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? 'expense, income, tracker, finance, budget']);
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
    <?php $this->head(); ?>
</head>

<body class="d-flex flex-column">
    <?php $this->beginBody() ?>

    <!-- ============================================================== -->
    <!-- Navigation Bar                                                 -->
    <!-- ============================================================== -->
    <?= $this->render('_navbar') ?>

    <!-- ============================================================== -->
    <!-- Main Content Area                                              -->
    <!-- ============================================================== -->
    <main id="main" class="main-content flex-grow-1" role="main">
        <div class="container-xxl py-4">
            <!-- Flash Messages -->
            <?= Alert::widget() ?>

            <!-- Page Content -->
            <?= $content ?>
        </div>
    </main>

    <!-- ============================================================== -->
    <!-- Footer                                                         -->
    <!-- ============================================================== -->
    <?= $this->render('_footer') ?>

    <!-- ============================================================== -->
    <!-- Global Modals                                                  -->
    <!-- ============================================================== -->
    <?= $this->render('_modals') ?>

    <!-- ============================================================== -->
    <!-- Toast Container                                                -->
    <!-- ============================================================== -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- ============================================================== -->
    <!-- Global Scripts                                                 -->
    <!-- ============================================================== -->
    <?= $this->render('_scripts') ?>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>
