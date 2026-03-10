<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 *
 * View for FiscalYearIncomeExpenseWidget
 *
 * Renders a monthly comparison table showing income vs expenses per month
 * with net balance. Styled using the same fy-summary-card / fy-table classes
 * as the Fiscal Year Expense Summary widget for visual consistency.
 *
 * @var yii\web\View $this
 * @var array $monthlyData Monthly income/expense records
 * @var array $totals Aggregated totals (income, expense, net, savingsRate)
 * @var string $fiscalYearLabel Fiscal year display label
 * @var string $containerClass Additional CSS classes
 * @var bool $showTrendIndicators Whether to show trend arrows
 * @var string $currencyCode Currency code for formatting
 * @var string $chartId Unique DOM ID (used for unique widget identification)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;

$formatter = Yii::$app->formatter;
$netIsPositive = $totals['net'] >= 0;
$currentMonthKey = date('Y-m');

// Count months with data
$monthsWithData = 0;
foreach ($monthlyData as $row) {
    if ($row['income'] > 0 || $row['expense'] > 0) {
        $monthsWithData++;
    }
}
?>

<div class="fiscal-year-income-expense-widget <?= Html::encode($containerClass) ?>">
    <div class="card fy-summary-card">
        <!-- ============================================================== -->
        <!-- Gradient Header                                                -->
        <!-- ============================================================== -->
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="fy-title"><?= Yii::t('app', 'Fiscal Year Income vs Expenses') ?></h5>
                <div class="fy-subtitle"><?= Yii::t('app', 'Monthly comparison of income and expenses') ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($fiscalYearLabel)): ?>
                    <span class="fy-badge"><?= Html::encode($fiscalYearLabel) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Comparison Table                                               -->
        <!-- ============================================================== -->
        <div class="fy-table-wrapper">
            <table class="fy-table">
                <thead>
                    <tr>
                        <th><?= Yii::t('app', 'Month') ?></th>
                        <th style="text-align: right;">
                            <i class="bi bi-arrow-up-circle text-success me-1"></i>
                            <?= Yii::t('app', 'Income') ?>
                        </th>
                        <th style="text-align: right;">
                            <i class="bi bi-arrow-down-circle text-danger me-1"></i>
                            <?= Yii::t('app', 'Expenses') ?>
                        </th>
                        <th style="text-align: right;">
                            <i class="bi bi-wallet2 me-1" style="color: var(--em-primary);"></i>
                            <?= Yii::t('app', 'Net Balance') ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyData as $row): ?>
                        <?php
                        $isCurrentMonth = ($row['monthKey'] === $currentMonthKey);
                        $isFutureMonth = ($row['monthKey'] > $currentMonthKey);
                        $hasData = ($row['income'] > 0 || $row['expense'] > 0);
                        $rowNet = $row['net'];
                        $netClass = $rowNet >= 0 ? 'text-success' : 'text-danger';
                        $netIcon = $rowNet >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill';

                        // Row CSS class
                        $rowClass = '';
                        if ($isCurrentMonth) {
                            $rowClass = 'current-month';
                        } elseif ($isFutureMonth) {
                            $rowClass = 'future-month';
                        }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <div class="month-cell">
                                    <?php if ($isCurrentMonth): ?>
                                        <span class="month-dot current"></span>
                                    <?php elseif ($isFutureMonth): ?>
                                        <span class="month-dot future"></span>
                                    <?php else: ?>
                                        <span class="month-dot past"></span>
                                    <?php endif; ?>
                                    <span><?= Html::encode($row['month']) ?></span>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($row['income'] > 0): ?>
                                    <span class="cell-value positive">
                                        <?= Yii::$app->currency->format($row['income']) ?>
                                    </span>
                                <?php elseif (!$isFutureMonth): ?>
                                    <span class="cell-value zero">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($row['expense'] > 0): ?>
                                    <span class="cell-value">
                                        <?= Yii::$app->currency->format($row['expense']) ?>
                                    </span>
                                <?php elseif (!$isFutureMonth): ?>
                                    <span class="cell-value zero">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($hasData): ?>
                                    <span class="cell-value <?= $rowNet >= 0 ? 'positive' : 'high' ?>">
                                        <i class="bi <?= $netIcon ?> me-1" style="font-size: 0.65rem;"></i>
                                        <?= Yii::$app->currency->format(abs($rowNet)) ?>
                                    </span>
                                <?php elseif (!$isFutureMonth): ?>
                                    <span class="cell-value zero">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- ============================================================== -->
                <!-- Totals & Grand Total                                           -->
                <!-- ============================================================== -->
                <tfoot>
                    <tr>
                        <td><strong><?= Yii::t('app', 'Totals') ?></strong></td>
                        <td style="text-align: right;">
                            <strong class="text-success">
                                <?= Yii::$app->currency->format($totals['income']) ?>
                            </strong>
                        </td>
                        <td style="text-align: right;">
                            <strong class="text-danger">
                                <?= Yii::$app->currency->format($totals['expense']) ?>
                            </strong>
                        </td>
                        <td style="text-align: right;">
                            <strong class="<?= $netIsPositive ? 'text-success' : 'text-danger' ?>">
                                <?= Yii::$app->currency->format(abs($totals['net'])) ?>
                            </strong>
                        </td>
                    </tr>
                    <tr class="grand-total">
                        <td>
                            <span class="badge bg-white <?= $netIsPositive ? 'text-success' : 'text-danger' ?> fw-bold px-3 py-2" style="font-size: 0.8rem;">
                                <?= $netIsPositive ? Yii::t('app', 'Net Savings') : Yii::t('app', 'Net Deficit') ?>
                            </span>
                        </td>
                        <td colspan="3" style="text-align: left;">
                            <strong style="font-size: 1rem;">
                                <?= Yii::$app->currency->format(abs($totals['net'])) ?>
                            </strong>
                            <?php if ($showTrendIndicators && $totals['income'] > 0): ?>
                                <span class="ms-2 opacity-75" style="font-size: 0.8rem;">
                                    (<?= abs($totals['savingsRate']) ?>% <?= $netIsPositive ? Yii::t('app', 'saved') : Yii::t('app', 'over budget') ?>)
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ============================================================== -->
        <!-- Footer                                                         -->
        <!-- ============================================================== -->
        <div class="card-footer">
            <span class="footer-stat">
                <i class="bi bi-calendar3"></i>
                <?= $monthsWithData ?> <?= Yii::t('app', 'months') ?>
            </span>
            <span class="footer-stat">
                <i class="bi bi-clock"></i>
                <?= Yii::t('app', 'Updated: {time}', [
                    'time' => $formatter->asDatetime(time(), 'short'),
                ]) ?>
            </span>
        </div>
    </div>
</div>
