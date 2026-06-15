<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Lifetime Overview Widget View
 *
 * Renders the lifetime financial overview panel with three key metrics:
 * - Cumulative Gross Revenue
 * - Aggregate Operating Expenditure
 * - Net Financial Position
 *
 * @var yii\web\View $this
 * @var array $metrics Financial metrics data
 * @var array $config Metric configuration
 * @var bool $showTrendIndicators Whether to show trend arrows
 * @var string|null $containerClass Additional CSS classes
 * @var string $title Widget title
 * @var string $subtitle Widget subtitle
 * @var string $widgetId Unique widget identifier
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use app\widgets\LifetimeOverviewWidget;
use yii\helpers\Html;

// Build container classes
$containerClasses = ['lifetime-overview-widget'];
if ($containerClass) {
    $containerClasses[] = $containerClass;
}
?>

<!-- ============================================================== -->
<!-- Lifetime Financial Overview Widget                             -->
<!-- ============================================================== -->
<div class="<?= implode(' ', $containerClasses) ?>" id="<?= Html::encode($widgetId) ?>">

    <!-- Widget Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 mb-1"><?= Html::encode($title) ?></h2>
            <p class="text-muted small mb-0"><?= Html::encode($subtitle) ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark">
                <i class="bi bi-infinity me-1"></i>
                <?= Yii::t('app', 'All Time') ?>
            </span>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="row g-0">

                <?php foreach ($metrics as $key => $metric): ?>
                    <?php
                    $metricConf = $config[$key];
                    $trendIndicator = LifetimeOverviewWidget::getTrendIndicator($metric['trend']);
                    $isNetNegative = ($key === 'netPosition' && ($metric['isNegative'] ?? false));
                    $isLastItem = ($key === array_key_last($metrics));
                    $colorClass = $metricConf['colorClass'];

                    // Override color for net position based on positive/negative
                    if ($key === 'netPosition') {
                        $colorClass = $isNetNegative ? 'danger' : 'success';
                    }
                    ?>

                    <div class="col-md-4 <?= !$isLastItem ? 'border-end' : '' ?>">
                        <div class="lo-metric-cell p-4 h-100">

                            <!-- Metric Header -->
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.03em;">
                                    <?= Html::encode($metricConf['label']) ?>
                                </span>
                                <?php if ($showTrendIndicators): ?>
                                    <span class="<?= $trendIndicator['class'] ?>"
                                          title="<?= Html::encode($trendIndicator['label']) ?>"
                                          data-bs-toggle="tooltip">
                                        <i class="<?= $trendIndicator['icon'] ?>"></i>
                                    </span>
                                <?php endif ?>
                            </div>

                            <!-- Metric Value -->
                            <div class="d-flex align-items-center mb-3">
                                <span class="metric-icon metric-icon--<?= $colorClass ?> me-3">
                                    <i class="<?= Html::encode($metricConf['icon']) ?> text-<?= $colorClass ?> fs-5"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <h3 class="mb-0 text-<?= $colorClass ?>" style="font-size: 1.4rem; font-weight: 700;">
                                        <?php if ($isNetNegative):
                                            ?><span>-</span><?php
                                        endif ?>
                                        <span class="metric-value" data-value="<?= Html::encode($metric['value']) ?>">
                                            <?= Html::encode($metric['formatted']) ?>
                                        </span>
                                    </h3>
                                </div>
                            </div>

                            <!-- Secondary Indicator -->
                            <?php if ($key === 'netPosition' && isset($metric['profitMargin'])): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= $metric['profitMargin'] >= 0 ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= $metric['profitMargin'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $metric['profitMargin'] >= 0 ? '+' : '' ?><?= $metric['profitMargin'] ?>%
                                    </span>
                                    <small class="text-muted"><?= Yii::t('app', 'profit margin') ?></small>
                                </div>
                            <?php elseif ($key === 'operatingExpenditure' && isset($metric['expenseRatio'])): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                        <?= $metric['expenseRatio'] ?>%
                                    </span>
                                    <small class="text-muted"><?= Yii::t('app', 'of revenue') ?></small>
                                </div>
                            <?php else: ?>
                                <!-- Description -->
                                <p class="text-muted small mb-0 d-none d-lg-block">
                                    <?= Html::encode($metricConf['description']) ?>
                                </p>
                            <?php endif ?>

                        </div>
                    </div>
                <?php endforeach ?>

            </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer bg-transparent py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-clock-history me-1"></i>
                    <?= Yii::t('app', 'Last updated: {time}', [
                        'time' => Yii::$app->formatter->asDatetime(time(), 'short'),
                    ]) ?>
                </small>
                <small class="text-muted">
                    <i class="bi bi-person me-1"></i>
                    <?= Yii::t('app', 'Account Metrics') ?>
                </small>
            </div>
        </div>

    </div>
</div>
<!-- End Lifetime Overview Widget -->
