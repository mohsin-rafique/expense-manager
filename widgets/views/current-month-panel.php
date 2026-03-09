<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Current Month Panel Widget View (Summary Mode) — Enhanced with Sparklines
 *
 * @var yii\web\View $this
 * @var string $widgetId Unique widget identifier
 * @var string $title Widget title
 * @var string|null $containerClass Additional CSS classes
 * @var float $income Current month income
 * @var float $expense Current month expense
 * @var float $profitLoss Current month profit/loss
 * @var array $icons Icon classes for each metric
 * @var string $currentMonthName Formatted current month name
 * @var bool $isProfit Whether profit/loss is positive
 * @var array $sparklineData Last 6 months data for sparklines
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.1.0
 */

use yii\helpers\Html;
use yii\helpers\Json;

$containerClasses = ['current-month-panel-widget'];
if ($containerClass) {
    $containerClasses[] = $containerClass;
}

$profitLossClass = $isProfit ? 'success' : 'danger';
$profitLossLabel = $isProfit ? Yii::t('app', 'Profit') : Yii::t('app', 'Loss');

// Calculate savings rate
$savingsRate = $income > 0 ? round(($profitLoss / $income) * 100, 1) : 0;

// Encode sparkline data for JS
$sparklineJson = Json::encode($sparklineData);
?>

<div class="<?= implode(' ', $containerClasses) ?>" id="<?= Html::encode($widgetId) ?>">
    <div class="card h-100 shadow-sm enhanced-stat-card">
        <div class="card-body p-0">

            <!-- Income Card -->
            <div class="stat-row income-row">
                <div class="stat-row-main">
                    <div class="stat-row-icon">
                        <span class="icon-wrapper income">
                            <i class="<?= Html::encode($icons['income']) ?>"></i>
                        </span>
                    </div>
                    <div class="stat-row-content">
                        <span class="stat-row-label">
                            <?= Yii::t('app', 'Current Month Income') ?>
                        </span>
                        <span class="stat-row-value text-success" data-count="<?= $income ?>">
                            <?= Yii::$app->currency->format($income) ?>
                        </span>
                    </div>
                    <div class="stat-row-sparkline">
                        <div id="sparkline-income-<?= $widgetId ?>"></div>
                    </div>
                </div>
            </div>

            <!-- Expense Card -->
            <div class="stat-row expense-row">
                <div class="stat-row-main">
                    <div class="stat-row-icon">
                        <span class="icon-wrapper expense">
                            <i class="<?= Html::encode($icons['expense']) ?>"></i>
                        </span>
                    </div>
                    <div class="stat-row-content">
                        <span class="stat-row-label">
                            <?= Yii::t('app', 'Current Month Expenses') ?>
                        </span>
                        <span class="stat-row-value text-danger" data-count="<?= $expense ?>">
                            <?= Yii::$app->currency->format($expense) ?>
                        </span>
                    </div>
                    <div class="stat-row-sparkline">
                        <div id="sparkline-expense-<?= $widgetId ?>"></div>
                    </div>
                </div>
                <!-- Expense vs Income Progress Bar -->
                <div class="stat-row-progress">
                    <?php $expenseRatio = $income > 0 ? min(100, round(($expense / $income) * 100)) : 0; ?>
                    <div class="progress-micro">
                        <div class="progress-micro-fill <?= $expenseRatio > 80 ? 'critical' : ($expenseRatio > 60 ? 'warning' : 'healthy') ?>"
                            style="width: <?= $expenseRatio ?>%"></div>
                    </div>
                    <span class="progress-micro-label"><?= $expenseRatio ?>% of income</span>
                </div>
            </div>

            <!-- Net Profit/Loss Card -->
            <div class="stat-row balance-row">
                <div class="stat-row-main">
                    <div class="stat-row-icon">
                        <span class="icon-wrapper <?= $profitLossClass ?>">
                            <i class="<?= Html::encode($icons['profit']) ?>"></i>
                        </span>
                    </div>
                    <div class="stat-row-content">
                        <span class="stat-row-label">
                            <?= Yii::t('app', 'Net {status}', ['status' => $profitLossLabel]) ?>
                        </span>
                        <span class="stat-row-value text-<?= $profitLossClass ?>" data-count="<?= abs($profitLoss) ?>">
                            <?php if (!$isProfit):
                                ?><span>-</span><?php
                            endif ?>
                            <?= Yii::$app->currency->format(abs($profitLoss)) ?>
                        </span>
                    </div>
                    <div class="stat-row-badge">
                        <span class="savings-badge <?= $isProfit ? 'positive' : 'negative' ?>">
                            <i class="bi <?= $isProfit ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i>
                            <?= abs($savingsRate) ?>% saved
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Card Footer -->
        <div class="card-footer bg-transparent py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= Html::encode($currentMonthName) ?>
                </small>
                <small class="text-muted">
                    <i class="bi bi-activity me-1"></i>
                    <?= Yii::t('app', '6-month trend') ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php
// Register sparkline charts
$js = <<<JS
(function() {
    var data = {$sparklineJson};

    var sparkOptions = {
        chart: { type: 'area', height: 40, sparkline: { enabled: true }, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        tooltip: {
            fixed: { enabled: false },
            x: { show: true, formatter: function(val, opts) { return data.labels[opts.dataPointIndex] || ''; } },
            y: { formatter: function(val) { return val.toLocaleString(); } },
            theme: 'dark'
        }
    };

    // Income sparkline
    new ApexCharts(document.querySelector('#sparkline-income-{$widgetId}'), Object.assign({}, sparkOptions, {
        series: [{ name: 'Income', data: data.income }],
        colors: ['#10b981'],
    })).render();

    // Expense sparkline
    new ApexCharts(document.querySelector('#sparkline-expense-{$widgetId}'), Object.assign({}, sparkOptions, {
        series: [{ name: 'Expense', data: data.expense }],
        colors: ['#ef4444'],
    })).render();
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>
