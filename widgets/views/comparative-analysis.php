<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Comparative Analysis Widget View
 *
 * Renders fiscal year financial metrics and expense category chart.
 *
 * @var yii\web\View $this
 * @var string $widgetId Unique widget identifier
 * @var string $title Widget title
 * @var string $subtitle Widget subtitle
 * @var string|null $containerClass Additional CSS classes
 * @var string $fiscalYearLabel Fiscal year display label
 * @var float $revenue Total revenue for fiscal year
 * @var float $expenditure Total expenditure for fiscal year
 * @var float $netPosition Net position (revenue - expenditure)
 * @var bool $isNetPositive Whether net position is positive
 * @var array $ExpenseCategory Category names => amounts
 * @var float $totalCategoryExpenditure Sum of all displayed categories
 * @var int $categoryCount Number of categories displayed
 * @var bool $showTrendIndicators Whether to show trend arrows
 * @var array $revenueTrend Revenue trend indicator config
 * @var array $expenditureTrend Expenditure trend indicator config
 * @var array $netTrend Net position trend indicator config
 * @var string $chartId Chart element ID
 * @var array $chartColors Chart color configuration
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

// Build container classes
$containerClasses = ['comparative-analysis-widget'];
if ($containerClass) {
    $containerClasses[] = $containerClass;
}

// Format currency values
$formattedRevenue = Yii::$app->currency->format($revenue);
$formattedExpenditure = Yii::$app->currency->format($expenditure);
$formattedNetPosition = Yii::$app->currency->format(abs($netPosition));

// Net position labels
$netLabel = $isNetPositive
    ? Yii::t('app', 'Net Surplus')
    : Yii::t('app', 'Net Deficit');
$netDescription = $isNetPositive
    ? Yii::t('app', 'You earned more than you spent')
    : Yii::t('app', 'You spent more than you earned');

// Savings rate calculation
$savingsRate = $revenue > 0 ? round(($netPosition / $revenue) * 100, 1) : 0;
?>

<!-- ============================================================== -->
<!-- Comparative Analysis Widget                                    -->
<!-- ============================================================== -->
<div class="<?= implode(' ', $containerClasses) ?>" id="<?= Html::encode($widgetId) ?>">

    <!-- Widget Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 mb-1"><?= Html::encode($title) ?></h2>
            <p class="text-muted small mb-0"><?= Html::encode($subtitle) ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary">
                <?= Html::encode($fiscalYearLabel) ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4 align-items-stretch">

        <!-- Financial Metrics Card -->
        <div class="col-xl-6 d-flex">
            <div class="card shadow-sm w-100 d-flex flex-column">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title h6 mb-0">
                        <?= Yii::t('app', 'Fiscal Year Summary') ?>
                    </h3>
                    <span class="badge <?= $isNetPositive ? 'bg-success' : 'bg-danger' ?>">
                        <?= $isNetPositive ? Yii::t('app', 'Surplus') : Yii::t('app', 'Deficit') ?>
                    </span>
                </div>

                <div class="card-body d-flex flex-column flex-grow-1 p-0">

                    <!-- Revenue Metric -->
                    <div class="ca-metric-row p-4 border-bottom">
                        <div class="d-flex align-items-center">
                            <span class="metric-icon metric-icon--success me-3">
                                <i class="bi bi-graph-up-arrow text-success fs-5"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">
                                        <?= Yii::t('app', 'Revenue') ?>
                                    </span>
                                    <?php if ($showTrendIndicators && $revenueTrend['percent'] !== null): ?>
                                        <span class="<?= $revenueTrend['class'] ?> small"
                                            title="<?= Html::encode($revenueTrend['label']) ?>">
                                            <i class="<?= $revenueTrend['icon'] ?>"></i>
                                            <?= $revenueTrend['percent'] > 0 ? '+' : '' ?><?= $revenueTrend['percent'] ?>%
                                        </span>
                                    <?php endif ?>
                                </div>
                                <span class="h4 mb-0 text-success fw-bold">
                                    <?= Html::encode($formattedRevenue) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Expenditure Metric -->
                    <div class="ca-metric-row p-4 border-bottom">
                        <div class="d-flex align-items-center">
                            <span class="metric-icon metric-icon--danger me-3">
                                <i class="bi bi-graph-down-arrow text-danger fs-5"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">
                                        <?= Yii::t('app', 'Expenditure') ?>
                                    </span>
                                    <?php if ($showTrendIndicators && $expenditureTrend['percent'] !== null): ?>
                                        <span class="<?= $expenditureTrend['class'] ?> small"
                                            title="<?= Html::encode($expenditureTrend['label']) ?>">
                                            <i class="<?= $expenditureTrend['icon'] ?>"></i>
                                            <?= $expenditureTrend['percent'] > 0 ? '+' : '' ?><?= $expenditureTrend['percent'] ?>%
                                        </span>
                                    <?php endif ?>
                                </div>
                                <span class="h4 mb-0 text-danger fw-bold">
                                    <?= Html::encode($formattedExpenditure) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Net Position Metric -->
                    <div class="ca-metric-row p-4 flex-grow-1 d-flex align-items-center">
                        <div class="d-flex align-items-center w-100">
                            <span class="metric-icon metric-icon--<?= $isNetPositive ? 'success' : 'danger' ?> me-3">
                                <i class="bi bi-wallet2 <?= $isNetPositive ? 'text-success' : 'text-danger' ?> fs-5"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">
                                        <?= Html::encode($netLabel) ?>
                                    </span>
                                    <?php if ($showTrendIndicators): ?>
                                        <span class="<?= $netTrend['class'] ?> small"
                                            title="<?= Html::encode($netTrend['label']) ?>">
                                            <i class="<?= $netTrend['icon'] ?>"></i>
                                        </span>
                                    <?php endif ?>
                                </div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <span class="h4 mb-0 <?= $isNetPositive ? 'text-success' : 'text-danger' ?> fw-bold">
                                        <?php if (!$isNetPositive):
                                            ?><span>-</span><?php
                                        endif ?>
                                        <?= Html::encode($formattedNetPosition) ?>
                                    </span>
                                    <?php if ($revenue > 0): ?>
                                        <span class="badge bg-light text-dark small" title="<?= Yii::t('app', 'Savings rate') ?>">
                                            <?= $savingsRate ?>% <?= Yii::t('app', 'saved') ?>
                                        </span>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Card Footer -->
                <div class="card-footer bg-transparent py-2 mt-auto">
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">
                            <i class="bi bi-calendar-range me-1"></i>
                            <?= Html::encode($fiscalYearLabel) ?>
                        </span>
                        <?php if ($showTrendIndicators): ?>
                            <span class="text-muted">
                                <i class="bi bi-arrow-left-right me-1"></i>
                                <?= Yii::t('app', 'vs previous year') ?>
                            </span>
                        <?php endif ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Category Chart Card -->
        <div class="col-xl-6 d-flex">
            <div class="card shadow-sm w-100 d-flex flex-column">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title h6 mb-0">
                        <?= Yii::t('app', 'Expenditure by Category') ?>
                    </h3>
                    <span class="badge bg-light text-dark">
                        <?= Html::encode($fiscalYearLabel) ?>
                    </span>
                </div>

                <div class="card-body d-flex flex-column flex-grow-1">
                    <?php if (empty($ExpenseCategory)): ?>
                        <!-- Empty State -->
                        <div class="text-center py-5 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-pie-chart text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">
                                <?= Yii::t('app', 'No expense data available for this period') ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Chart Container -->
                        <div id="<?= Html::encode($chartId) ?>"
                            class="flex-grow-1"
                            style="min-height: 350px;"
                            role="img"
                            aria-label="<?= Yii::t('app', 'Horizontal bar chart showing expenses by category') ?>">
                        </div>
                    <?php endif ?>
                </div>

                <?php if (!empty($ExpenseCategory)): ?>
                    <!-- Card Footer with Summary -->
                    <div class="card-footer bg-transparent py-2 mt-auto">
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted">
                                <i class="bi bi-tags me-1"></i>
                                <?= Yii::t('app', '{count} categories', ['count' => $categoryCount]) ?>
                            </span>
                            <span class="fw-semibold">
                                <?= Yii::t('app', 'Total') ?>:
                                <span class="text-danger">
                                    <?= Yii::$app->currency->format($totalCategoryExpenditure) ?>
                                </span>
                            </span>
                        </div>
                    </div>
                <?php endif ?>

            </div>
        </div>

    </div>

</div>
<!-- End Comparative Analysis Widget -->
