<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Exported Data View
 *
 * Displays exported data details and download options.
 *
 * @var yii\web\View $this
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Exported Data');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Profile'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Settings'), 'url' => ['settings']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="exported-data-view">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <i class="bi bi-file-earmark-arrow-down text-primary me-2"></i>
                    <?= Html::encode($this->title) ?>
                </h1>
                <p class="page-subtitle text-muted mb-0">
                    <?= Yii::t('app', 'View and download your exported data files') ?>
                </p>
            </div>
            <div class="col-auto">
                <?= Html::a(
                    '<i class="bi bi-arrow-left me-1"></i>' . Yii::t('app', 'Back to Settings'),
                    ['settings', 'tab' => 'backups'],
                    ['class' => 'btn btn-outline-secondary']
                ) ?>
            </div>
        </div>
    </div>

    <!-- Export Options Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-download text-success me-2"></i>
                <?= Yii::t('app', 'Export Options') ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Export to Excel -->
                <div class="col-md-4">
                    <div class="export-option-card">
                        <div class="export-option-icon bg-success-subtle">
                            <i class="bi bi-file-earmark-excel text-success"></i>
                        </div>
                        <h6 class="export-option-title"><?= Yii::t('app', 'Excel Export') ?></h6>
                        <p class="export-option-desc text-muted small">
                            <?= Yii::t('app', 'Download your data as an Excel spreadsheet (.xlsx)') ?>
                        </p>
                        <?= Html::a(
                            '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export to Excel'),
                            ['export-excel'],
                            ['class' => 'btn btn-sm btn-outline-success']
                        ) ?>
                    </div>
                </div>

                <!-- Export to CSV -->
                <div class="col-md-4">
                    <div class="export-option-card">
                        <div class="export-option-icon bg-info-subtle">
                            <i class="bi bi-filetype-csv text-info"></i>
                        </div>
                        <h6 class="export-option-title"><?= Yii::t('app', 'CSV Export') ?></h6>
                        <p class="export-option-desc text-muted small">
                            <?= Yii::t('app', 'Download your data as a CSV file for easy import') ?>
                        </p>
                        <?= Html::a(
                            '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export to CSV'),
                            ['export-csv'],
                            ['class' => 'btn btn-sm btn-outline-info']
                        ) ?>
                    </div>
                </div>

                <!-- Export to PDF -->
                <div class="col-md-4">
                    <div class="export-option-card">
                        <div class="export-option-icon bg-danger-subtle">
                            <i class="bi bi-file-earmark-pdf text-danger"></i>
                        </div>
                        <h6 class="export-option-title"><?= Yii::t('app', 'PDF Report') ?></h6>
                        <p class="export-option-desc text-muted small">
                            <?= Yii::t('app', 'Generate a formatted PDF report of your finances') ?>
                        </p>
                        <?= Html::a(
                            '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Generate PDF'),
                            ['export-pdf'],
                            ['class' => 'btn btn-sm btn-outline-danger']
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coming Soon Notice -->
    <div class="card">
        <div class="card-body">
            <div class="empty-state py-4">
                <div class="empty-state-icon mb-3">
                    <i class="bi bi-rocket-takeoff"></i>
                </div>
                <h5 class="empty-state-title"><?= Yii::t('app', 'More Export Options Coming Soon') ?></h5>
                <p class="empty-state-text text-muted">
                    <?= Yii::t('app', 'We\'re working on additional export formats and scheduled backup features. Stay tuned!') ?>
                </p>
            </div>
        </div>
    </div>
</div>
