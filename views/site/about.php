<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * About Page View
 *
 * Displays information about the Expense Manager application including:
 * - Application logo and branding
 * - Version information
 * - Project description
 * - Links to GitHub repository
 * - License information
 *
 * This is a static informational page for the open-source project.
 *
 * @var yii\web\View $this The view object
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'About');
$this->params['breadcrumbs'][] = $this->title;

// Application metadata
$appVersion = '1.0.0';
$githubUrl = 'https://github.com/mohsin-rafique/expense-manager';
$issuesUrl = 'https://github.com/mohsin-rafique/expense-manager/issues';
?>

<div class="site-about">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- ============================================================== -->
            <!-- About Card                                                     -->
            <!-- ============================================================== -->
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">

                    <!-- Application Logo -->
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="80" height="80">
                            <defs>
                                <linearGradient id="iconGradAbout" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#2563eb" />
                                    <stop offset="50%" style="stop-color:#3b82f6" />
                                    <stop offset="100%" style="stop-color:#22c55e" />
                                </linearGradient>
                            </defs>
                            <rect x="6" y="18" width="52" height="42" rx="8" fill="url(#iconGradAbout)" />
                            <path d="M12 18 Q12 8 22 8 L42 8 Q52 8 52 18" fill="none" stroke="url(#iconGradAbout)" stroke-width="5" stroke-linecap="round" />
                            <rect x="36" y="30" width="16" height="12" rx="3" fill="#ffffff" opacity="0.9" />
                            <path d="M16 52 L26 38 L36 46 L50 28" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M42 28 L50 28 L50 36" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <!-- Application Name & Version -->
                    <h1 class="h3 mb-1"><?= Html::encode(Yii::$app->name) ?></h1>
                    <p class="text-muted mb-4">
                        <?= Yii::t('app', 'Version {version}', ['version' => $appVersion]) ?>
                    </p>

                    <!-- Description -->
                    <p class="lead text-secondary mb-4 px-lg-5">
                        <?= Yii::t('app', 'A free, open-source expense tracking application built with Yii2 Framework. Track your income, manage expenses, and take control of your financial health.') ?>
                    </p>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                        <?= Html::a(
                            '<i class="bi bi-github me-2"></i>' . Yii::t('app', 'View on GitHub'),
                            $githubUrl,
                            [
                                'class' => 'btn btn-dark btn-lg',
                                'target' => '_blank',
                                'rel' => 'noopener noreferrer',
                            ]
                        ) ?>
                        <?= Html::a(
                            '<i class="bi bi-bug me-2"></i>' . Yii::t('app', 'Report Issue'),
                            $issuesUrl,
                            [
                                'class' => 'btn btn-outline-secondary btn-lg',
                                'target' => '_blank',
                                'rel' => 'noopener noreferrer',
                            ]
                        ) ?>
                    </div>

                    <hr class="my-4">

                    <!-- Features List -->
                    <div class="row text-start mb-4">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?= Yii::t('app', 'Track income and expenses') ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?= Yii::t('app', 'Customizable categories') ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?= Yii::t('app', 'Export to Excel') ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?= Yii::t('app', 'Database backup & restore') ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?= Yii::t('app', 'Multi-currency support') ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?= Yii::t('app', 'Responsive design') ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Technology Stack -->
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted mb-3">
                            <?= Yii::t('app', 'Built With') ?>
                        </h6>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">Yii2 Framework</span>
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">Bootstrap 5</span>
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">MySQL</span>
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">PHP 8+</span>
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">JavaScript</span>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- License Information -->
                    <div class="text-muted">
                        <p class="small mb-1">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            <?= Yii::t('app', 'Released under the {license}', [
                                'license' => Html::a(
                                    'MIT License',
                                    $githubUrl . '/blob/main/LICENSE',
                                    [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer',
                                        'class' => 'text-decoration-none',
                                    ]
                                ),
                            ]) ?>
                        </p>
                        <p class="small mb-0">
                            <?= Yii::t('app', 'Made with {heart} for the open-source community', [
                                'heart' => '<i class="bi bi-heart-fill text-danger"></i>',
                            ]) ?>
                        </p>
                    </div>

                </div>
            </div>

            <!-- ============================================================== -->
            <!-- Contributors Section (Optional)                                -->
            <!-- ============================================================== -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>
                        <?= Yii::t('app', 'Contributors') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        <?= Yii::t('app', 'This project is made possible by the following contributors:') ?>
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <?= Html::a(
                            '<img src="https://github.com/mohsin-rafique.png" width="40" height="40" class="rounded-circle" alt="Mohsin Rafique"> ',
                            'https://github.com/mohsin-rafique',
                            [
                                'target' => '_blank',
                                'rel' => 'noopener noreferrer',
                                'title' => 'Mohsin Rafique',
                                'data-bs-toggle' => 'tooltip',
                            ]
                        ) ?>
                        <!-- Add more contributors as needed -->
                    </div>
                    <div class="mt-3">
                        <?= Html::a(
                            '<i class="bi bi-plus-circle me-1"></i>' . Yii::t('app', 'Become a Contributor'),
                            $githubUrl . '/blob/main/CONTRIBUTING.md',
                            [
                                'class' => 'btn btn-sm btn-outline-primary',
                                'target' => '_blank',
                                'rel' => 'noopener noreferrer',
                            ]
                        ) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
