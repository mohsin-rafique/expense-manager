<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Evolution Panel Widget View
 *
 * Displays month-over-month comparison of income, expense, and balance
 * in a tabular format with trend indicators.
 *
 * @var yii\web\View $this
 * @var string $widgetId Unique widget identifier
 * @var string $title Widget title
 * @var string|null $containerClass Additional CSS classes
 * @var string $currencyCode Currency code for formatting
 * @var float $currentMonthIncome Current month income
 * @var float $currentMonthExpense Current month expense
 * @var float $previousMonthIncome Previous month income
 * @var float $previousMonthExpense Previous month expense
 * @var float $balancePreviousMonth Previous month balance
 * @var float $balanceCurrentMonth Current month balance (with carryover)
 * @var string $currentMonthName Formatted current month name
 * @var string $previousMonthName Formatted previous month name
 * @var bool $isPositiveBalance Whether current balance is positive
 * @var array $incomeTrend Income trend indicator config
 * @var array $expenseTrend Expense trend indicator config
 * @var array $balanceTrend Balance trend indicator config
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

// Build container classes
$containerClasses = ['evolution-panel-widget'];
if ($containerClass) {
    $containerClasses[] = $containerClass;
}

// Calculate current month income with previous balance carryover
$currentMonthIncomeWithCarryover = $currentMonthIncome + $balancePreviousMonth;

// Balance badge styling
$balanceBadgeClass = $isPositiveBalance ? 'bg-success' : 'bg-danger';
$balanceText = $isPositiveBalance ? Yii::t('app', 'Surplus') : Yii::t('app', 'Deficit');
?>

<!-- ============================================================== -->
<!-- Evolution Panel Widget                                         -->
<!-- ============================================================== -->
<div class="<?= implode(' ', $containerClasses) ?>" id="<?= Html::encode($widgetId) ?>">
    <div class="card h-100 shadow-sm d-flex flex-column">

        <!-- Card Header -->
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title h6 mb-0">
                <?= Html::encode($title) ?>
            </h3>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted"><?= Yii::t('app', 'Monthly Balance') ?></small>
                <span class="badge <?= $balanceBadgeClass ?>">
                    <?= $balanceText ?>
                </span>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body d-flex flex-column flex-grow-1">
            <div class="table-responsive flex-grow-1 d-flex flex-column justify-content-center">
                <table class="table table-borderless align-middle mb-0">

                    <!-- Table Header -->
                    <thead>
                        <tr class="text-muted small">
                            <th class="ps-0" style="width: 28%;">
                                <i class="bi bi-bar-chart me-1"></i>
                                <?= Yii::t('app', 'Metric') ?>
                            </th>
                            <th class="text-end"><?= Html::encode($previousMonthName) ?></th>
                            <th class="text-end"><?= Html::encode($currentMonthName) ?></th>
                            <th class="text-center" style="width: 90px;"><?= Yii::t('app', 'Trend') ?></th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                        <!-- Income Row -->
                        <tr>
                            <td class="ps-0 py-3">
                                <div class="d-flex align-items-center">
                                    <span class="metric-icon metric-icon--success me-2">
                                        <i class="bi bi-graph-up-arrow text-success"></i>
                                    </span>
                                    <span class="fw-medium"><?= Yii::t('app', 'Income') ?></span>
                                </div>
                            </td>
                            <td class="text-end text-success fw-medium py-3">
                                <?= Yii::$app->currency->format($previousMonthIncome) ?>
                            </td>
                            <td class="text-end text-success fw-medium py-3">
                                <?= Yii::$app->currency->format($currentMonthIncomeWithCarryover) ?>
                                <?php if ($balancePreviousMonth != 0): ?>
                                    <br>
                                    <small class="text-muted fw-normal">
                                        (<?= Yii::t('app', 'incl. {amount} carryover', [
                                                'amount' => Yii::$app->currency->format($balancePreviousMonth),
                                            ]) ?>)
                                    </small>
                                <?php endif ?>
                            </td>
                            <td class="text-center py-3">
                                <span class="trend-indicator <?= $incomeTrend['class'] ?>" title="<?= $incomeTrend['percent'] ?>%">
                                    <i class="<?= $incomeTrend['icon'] ?>"></i>
                                    <?php if ($incomeTrend['percent'] != 0): ?>
                                        <small class="ms-1"><?= $incomeTrend['percent'] > 0 ? '+' : '' ?><?= $incomeTrend['percent'] ?>%</small>
                                    <?php endif ?>
                                </span>
                            </td>
                        </tr>

                        <!-- Expense Row -->
                        <tr>
                            <td class="ps-0 py-3">
                                <div class="d-flex align-items-center">
                                    <span class="metric-icon metric-icon--danger me-2">
                                        <i class="bi bi-graph-down-arrow text-danger"></i>
                                    </span>
                                    <span class="fw-medium"><?= Yii::t('app', 'Expense') ?></span>
                                </div>
                            </td>
                            <td class="text-end text-danger fw-medium py-3">
                                <?= Yii::$app->currency->format($previousMonthExpense) ?>
                            </td>
                            <td class="text-end text-danger fw-medium py-3">
                                <?= Yii::$app->currency->format($currentMonthExpense) ?>
                            </td>
                            <td class="text-center py-3">
                                <span class="trend-indicator <?= $expenseTrend['class'] ?>" title="<?= $expenseTrend['percent'] ?>%">
                                    <i class="<?= $expenseTrend['icon'] ?>"></i>
                                    <?php if ($expenseTrend['percent'] != 0): ?>
                                        <small class="ms-1"><?= $expenseTrend['percent'] > 0 ? '+' : '' ?><?= $expenseTrend['percent'] ?>%</small>
                                    <?php endif ?>
                                </span>
                            </td>
                        </tr>

                        <!-- Balance Row -->
                        <tr class="border-top">
                            <td class="ps-0 py-3">
                                <div class="d-flex align-items-center">
                                    <span class="metric-icon metric-icon--primary me-2">
                                        <i class="bi bi-wallet2 text-primary"></i>
                                    </span>
                                    <span class="fw-bold"><?= Yii::t('app', 'Balance') ?></span>
                                </div>
                            </td>
                            <td class="text-end fw-bold py-3 <?= $balancePreviousMonth >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?php if ($balancePreviousMonth < 0):
                                    ?><span>-</span><?php
                                endif ?>
                                <?= Yii::$app->currency->format(abs($balancePreviousMonth)) ?>
                            </td>
                            <td class="text-end fw-bold py-3 <?= $isPositiveBalance ? 'text-success' : 'text-danger' ?>">
                                <?php if (!$isPositiveBalance):
                                    ?><span>-</span><?php
                                endif ?>
                                <?= Yii::$app->currency->format(abs($balanceCurrentMonth)) ?>
                            </td>
                            <td class="text-center py-3">
                                <span class="trend-indicator <?= $balanceTrend['class'] ?>" title="<?= $balanceTrend['percent'] ?>%">
                                    <i class="<?= $balanceTrend['icon'] ?>"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer bg-transparent py-2 mt-auto">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-arrow-left-right me-1"></i>
                    <?= Yii::t('app', 'Month-over-month comparison') ?>
                </small>
                <small class="text-muted">
                    <i class="bi bi-clock-history me-1"></i>
                    <?= Yii::t('app', 'Updated: {time}', [
                        'time' => Yii::$app->formatter->asTime(time(), 'short'),
                    ]) ?>
                </small>
            </div>
        </div>

    </div>
</div>
<!-- End Evolution Panel Widget -->
