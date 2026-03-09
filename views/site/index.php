<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Dashboard View
 *
 * Main dashboard displaying financial overview, trends, and analytics.
 * All data comes from the DashboardViewModel — no business logic here.
 *
 * @var yii\web\View $this
 * @var app\viewmodels\DashboardViewModel $vm
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;
use app\widgets\CurrentMonthPanelWidget;
use app\widgets\MonthlyPerformanceWidget;
use app\widgets\ExpensesByCategoryWidget;
use app\widgets\FiscalYearExpenseSummaryByMonth;
use app\widgets\ComparativeAnalysisPanel;
use app\widgets\LifetimeOverviewWidget;
use app\assets\DashboardAsset;

DashboardAsset::register($this);

// Page configuration
$this->title = Yii::t('app', 'Dashboard');
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- ============================================================== -->
<!-- Dashboard Header                                               -->
<!-- ============================================================== -->
<div class="dashboard-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Welcome back! Here\'s your financial overview.') ?>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <!-- Fiscal Year Selector -->
            <div class="fy-selector">
                <select onchange="window.location.href='<?= Url::to(['site/index']) ?>?fy=' + encodeURIComponent(this.value)">
                    <?php foreach ($vm->availableFiscalYears as $fy): ?>
                        <option value="<?= Html::encode($fy['label']) ?>"
                            <?= $vm->isFiscalYearActive($fy['label']) ? 'selected' : '' ?>>
                            <?= Html::encode($fy['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="fy-updated">
                    <i class="bi bi-clock"></i>
                    <?= Yii::t('app', 'Last updated: {date}', ['date' => $vm->lastUpdated]) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- Current Month Panel                                            -->
<!-- Quick overview of this month's financial status                -->
<!-- ============================================================== -->
<section class="dashboard-section mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0"><?= Yii::t('app', 'Current Month Overview') ?></h2>
        <span class="badge bg-primary"><?= Html::encode($vm->currentMonth) ?></span>
    </div>

    <div class="row g-4">
        <!-- Summary Stats -->
        <div class="col-xl-6">
            <?= CurrentMonthPanelWidget::widget(['mode' => 'summary']) ?>
        </div>

        <!-- Performance Donut Chart -->
        <div class="col-xl-6">
            <?= MonthlyPerformanceWidget::widget() ?>
        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- Financial Trends Panel                                         -->
<!-- Month-over-month financial evolution                           -->
<!-- ============================================================== -->
<section class="dashboard-section mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0">
            <?= Yii::t('app', 'Financial Trends') ?>
        </h2>
    </div>

    <div class="row g-4 align-items-stretch">
        <!-- Evolution Chart -->
        <div class="col-xl-6 d-flex">
            <?= CurrentMonthPanelWidget::widget([
                'mode' => 'evolution',
                'containerClass' => 'w-100',
            ]) ?>
        </div>

        <!-- Category Breakdown -->
        <div class="col-xl-6 d-flex">
            <?= ExpensesByCategoryWidget::widget([
                'maxCategories' => $vm->maxCategories,
                'containerClass' => 'w-100',
            ]) ?>
        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- Fiscal Year Summary                                            -->
<!-- Year-to-date expense breakdown by month                        -->
<!-- ============================================================== -->
<?= FiscalYearExpenseSummaryByMonth::widget([
    'fiscalStartDate' => $vm->getFiscalStartDate(),
    'fiscalEndDate' => $vm->getFiscalEndDate(),
    'fiscalYearLabel' => $vm->getFiscalYearLabel(),
    'title' => Yii::t('app', 'Fiscal Year Expense Summary'),
    'subtitle' => Yii::t('app', 'Monthly breakdown by category'),
    'enableExport' => $vm->enableExport,
    'enableFiltering' => $vm->enableFiltering,
    'containerClass' => 'mb-4',
]) ?>

<!-- ============================================================== -->
<!-- Comparative Analysis Panel                                     -->
<!-- Period-over-period comparison and insights                     -->
<!-- ============================================================== -->
<?= ComparativeAnalysisPanel::widget([
    'fiscalStartDate' => $vm->getFiscalStartDate(),
    'fiscalEndDate' => $vm->getFiscalEndDate(),
    'fiscalYearLabel' => $vm->getFiscalYearLabel(),
    'showTrendIndicators' => $vm->showTrendIndicators,
    'enablePreviousPeriodComparison' => $vm->enablePreviousPeriodComparison,
    'containerClass' => 'mb-4',
    'maxCategories' => $vm->maxCategories,
]) ?>

<!-- ============================================================== -->
<!-- Lifetime Overview Panel                                        -->
<!-- All-time financial statistics                                  -->
<!-- ============================================================== -->
<?= LifetimeOverviewWidget::widget([
    'showTrendIndicators' => $vm->showTrendIndicators,
    'currencyCode' => $vm->currencyCode,
    'startDate' => $vm->lifetimeStartDate,
    'containerClass' => 'mb-4',
]) ?>

<?php
/**
 * Register ApexCharts library
 */
$this->registerJsFile('/libs/apexcharts/apexcharts.min.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
