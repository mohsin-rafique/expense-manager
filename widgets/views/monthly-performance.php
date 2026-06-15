<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Monthly Performance Widget View
 *
 * @var yii\web\View $this
 * @var string $widgetId Unique widget identifier
 * @var string $title Widget title
 * @var string|null $containerClass Additional CSS classes
 * @var string $currentMonthName Formatted current month name
 * @var float $income Current month income
 * @var float $expense Current month expense
 * @var float $balance Current month balance
 * @var float $savingsRate Savings as percentage of income
 * @var float $expenseRate Expenses as percentage of income
 * @var bool $hasData Whether there is data to display
 * @var int $chartHeight Chart height in pixels
 * @var array $trend Monthly trend data
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.1.0
 */

use yii\helpers\Html;
use yii\helpers\Json;

$containerClasses = ['monthly-performance-widget'];
if ($containerClass) {
    $containerClasses[] = $containerClass;
}

$chartId = 'performanceRadialChart_' . $widgetId;
$trendChartId = 'performanceTrendChart_' . $widgetId;
$trendJson = Json::encode($trend);
?>

<!-- ============================================================== -->
<!-- Monthly Performance Widget                                     -->
<!-- ============================================================== -->
<div class="<?= implode(' ', $containerClasses) ?>" id="<?= Html::encode($widgetId) ?>">
    <div class="card h-100 shadow-sm enhanced-performance-card">

        <!-- Card Header -->
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title h6 mb-0">
                <?= Html::encode($title) ?>
            </h3>
            <span class="badge bg-light text-dark">
                <?= Html::encode($currentMonthName) ?>
            </span>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <?php if ($hasData): ?>
                <!-- Radial Chart -->
                <div class="perf-chart-wrapper">
                    <div id="<?= Html::encode($chartId) ?>"></div>
                </div>

                <!-- Metric Cards Row -->
                <div class="perf-metrics">
                    <div class="perf-metric-item income">
                        <div class="perf-metric-dot"></div>
                        <div class="perf-metric-info">
                            <span class="perf-metric-label"><?= Yii::t('app', 'Income') ?></span>
                            <span class="perf-metric-value"><?= Yii::$app->currency->format($income) ?></span>
                        </div>
                    </div>
                    <div class="perf-metric-item expense">
                        <div class="perf-metric-dot"></div>
                        <div class="perf-metric-info">
                            <span class="perf-metric-label"><?= Yii::t('app', 'Expenses') ?></span>
                            <span class="perf-metric-value"><?= Yii::$app->currency->format($expense) ?></span>
                        </div>
                    </div>
                    <div class="perf-metric-item savings">
                        <div class="perf-metric-dot"></div>
                        <div class="perf-metric-info">
                            <span class="perf-metric-label"><?= Yii::t('app', 'Savings') ?></span>
                            <span class="perf-metric-value"><?= Yii::$app->currency->format($balance) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Savings Trend Sparkline -->
                <div class="perf-trend">
                    <div class="perf-trend-header">
                        <span class="perf-trend-label">
                            <i class="bi bi-graph-up me-1"></i>
                            <?= Yii::t('app', '6-Month Savings Trend') ?>
                        </span>
                    </div>
                    <div id="<?= Html::encode($trendChartId) ?>"></div>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-pie-chart text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0"><?= Yii::t('app', 'No financial data for this month yet') ?></p>
                    <p class="text-muted small"><?= Yii::t('app', 'Start by adding income or expenses') ?></p>
                </div>
            <?php endif ?>
        </div>

    </div>
</div>

<?php if ($hasData):
    $savingsRateJs = round($savingsRate);
    $expenseRateJs = round($expenseRate);

    $js = <<<JS
(function() {
    'use strict';
    if (typeof ApexCharts === 'undefined') return;

    var trend = {$trendJson};

    // ── Radial Bar Chart ──
    var radialOptions = {
        series: [{$savingsRateJs}, {$expenseRateJs}],
        chart: {
            type: 'radialBar',
            height: 220,
            fontFamily: 'Inter, -apple-system, sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 1000 }
        },
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: {
                    size: '50%',
                    margin: 0,
                    background: 'transparent'
                },
                track: {
                    background: '#f1f5f9',
                    strokeWidth: '100%',
                    margin: 6
                },
                dataLabels: {
                    name: {
                        show: true,
                        fontSize: '11px',
                        fontWeight: 500,
                        color: '#94a3b8',
                        offsetY: -8
                    },
                    value: {
                        show: true,
                        fontSize: '22px',
                        fontWeight: 700,
                        color: '#1e293b',
                        offsetY: 4,
                        formatter: function(val) { return val + '%'; }
                    },
                    total: {
                        show: true,
                        label: 'Saved',
                        fontSize: '11px',
                        fontWeight: 500,
                        color: '#94a3b8',
                        formatter: function(w) {
                            return w.globals.seriesTotals[0] + '%';
                        }
                    }
                }
            }
        },
        labels: ['Savings', 'Spent'],
        colors: ['#10b981', '#ef4444'],
        stroke: { lineCap: 'round' },
        legend: { show: false }
    };

    var radialChart = document.querySelector('#{$chartId}');
    if (radialChart) {
        new ApexCharts(radialChart, radialOptions).render();
    }

    // ── Savings Trend Line ──
    var trendOptions = {
        series: [{
            name: 'Savings Rate',
            data: trend.rates
        }],
        chart: {
            type: 'area',
            height: 60,
            sparkline: { enabled: true },
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#6366f1'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
        },
        tooltip: {
            fixed: { enabled: false },
            x: {
                show: true,
                formatter: function(val, opts) {
                    return trend.labels[opts.dataPointIndex] || '';
                }
            },
            y: {
                formatter: function(val) { return val + '% saved'; }
            },
            theme: 'dark'
        },
        yaxis: { min: 0, max: 100 }
    };

    var trendChart = document.querySelector('#{$trendChartId}');
    if (trendChart) {
        new ApexCharts(trendChart, trendOptions).render();
    }
})();
JS;

    $this->registerJs($js, \yii\web\View::POS_END);
endif; ?>
