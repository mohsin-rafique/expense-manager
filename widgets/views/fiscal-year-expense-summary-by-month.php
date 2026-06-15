<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Fiscal Year Expense Summary Widget View
 *
 * Renders a monthly expense breakdown table with category filtering,
 * heat map visualization, and Excel export capabilities.
 *
 * @var yii\web\View $this
 * @var string $widgetId Unique widget identifier
 * @var string $title Widget title
 * @var string $subtitle Widget subtitle
 * @var string $fiscalYearLabel Fiscal year display label
 * @var string|null $containerClass Additional CSS classes
 * @var array $months Month range (ym => label)
 * @var array $categories Ordered categories (id => name)
 * @var array $pivot Expense data (month => category => amount)
 * @var array $totals Category totals
 * @var float $grandTotal Grand total amount
 * @var bool $enableExport Whether export is enabled
 * @var bool $enableFiltering Whether filtering is enabled
 * @var string $exportUrl Export URL
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

// Build container classes
$containerClasses = ['fiscal-year-expense-widget'];
if ($containerClass) {
    $containerClasses[] = $containerClass;
}

// Count categories for colspan
$categoryCount = count($categories);

// Current month for highlighting
$currentYm = date('Y-m');

// Calculate max value per category for heat map
$maxPerCategory = [];
foreach ($categories as $catId => $catName) {
    $maxPerCategory[$catId] = 0;
    foreach ($months as $ym => $label) {
        $val = $pivot[$ym][$catId] ?? 0;
        if ($val > $maxPerCategory[$catId]) {
            $maxPerCategory[$catId] = $val;
        }
    }
}

/**
 * Returns heat map CSS class (heat-1 to heat-5) based on value relative to max.
 */
function getHeatClass(float $value, float $max): string
{
    if ($value <= 0 || $max <= 0) {
        return '';
    }
    $ratio = $value / $max;
    if ($ratio >= 0.8) {
        return 'heat-5';
    }
    if ($ratio >= 0.6) {
        return 'heat-4';
    }
    if ($ratio >= 0.4) {
        return 'heat-3';
    }
    if ($ratio >= 0.2) {
        return 'heat-2';
    }
    return 'heat-1';
}

/**
 * Returns month status: 'current', 'past', or 'future'.
 */
function getMonthStatus(string $ym, string $currentYm): string
{
    if ($ym === $currentYm) {
        return 'current';
    }
    return ($ym < $currentYm) ? 'past' : 'future';
}
?>

<!-- ============================================================== -->
<!-- Fiscal Year Expense Summary Widget                             -->
<!-- ============================================================== -->
<div class="<?= implode(' ', $containerClasses) ?>" id="<?= Html::encode($widgetId) ?>">

    <!-- Main Card -->
    <div class="fy-summary-card card">

        <!-- ── Gradient Header ── -->
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fy-title"><?= Html::encode($title) ?></div>
                <div class="fy-subtitle"><?= Html::encode($subtitle) ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="fy-badge">
                    <?= Html::encode($fiscalYearLabel) ?>
                </span>

                <div class="fy-actions d-flex gap-2">
                    <?php if ($enableExport): ?>
                        <?= Html::a(
                            '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export'),
                            $exportUrl,
                            ['class' => 'btn']
                        ) ?>
                    <?php endif ?>

                    <?php if ($enableFiltering && !empty($categories)): ?>
                        <div class="dropdown">
                            <button class="btn dropdown-toggle"
                                type="button"
                                id="categoryFilterDropdown-<?= $widgetId ?>"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false">
                                <i class="bi bi-funnel me-1"></i>
                                <?= Yii::t('app', 'Filter') ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-3"
                                style="width: 280px; max-height: 350px; overflow-y: auto;">

                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                    <a href="#"
                                        class="text-primary small text-decoration-none"
                                        id="selectAllCategories-<?= $widgetId ?>">
                                        <?= Yii::t('app', 'Select All') ?>
                                    </a>
                                    <a href="#"
                                        class="text-danger small text-decoration-none"
                                        id="deselectAllCategories-<?= $widgetId ?>">
                                        <?= Yii::t('app', 'Deselect All') ?>
                                    </a>
                                </div>

                                <?php foreach ($categories as $catId => $catName): ?>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input category-checkbox"
                                            type="checkbox"
                                            value="<?= Html::encode($catId) ?>"
                                            id="cat_<?= $widgetId ?>_<?= $catId ?>"
                                            checked>
                                        <label class="form-check-label small"
                                            for="cat_<?= $widgetId ?>_<?= $catId ?>">
                                            <?= Html::encode($catName) ?>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- ── Table Body ── -->
        <?php if (empty($categories)): ?>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="bi bi-table text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">
                        <?= Yii::t('app', 'No expense data available for this fiscal year') ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="fy-table-wrapper">
                <table class="fy-table">

                    <!-- Table Header -->
                    <thead>
                        <tr>
                            <th class="text-start" style="min-width: 140px;">
                                <?= Yii::t('app', 'Month') ?>
                            </th>
                            <?php foreach ($categories as $catId => $catName): ?>
                                <th class="category-col sortable"
                                    data-cat-id="<?= Html::encode($catId) ?>">
                                    <?= Html::encode($catName) ?>
                                </th>
                            <?php endforeach ?>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                        <?php foreach ($months as $ym => $label):
                            $monthStatus = getMonthStatus($ym, $currentYm);
                            $rowClass = '';
                            if ($monthStatus === 'current') {
                                $rowClass = 'current-month';
                            } elseif ($monthStatus === 'future') {
                                $rowClass = 'future-month';
                            }
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="text-start">
                                    <div class="month-cell">
                                        <span class="month-dot <?= $monthStatus ?>"></span>
                                        <?= Html::encode($label) ?>
                                    </div>
                                </td>
                                <?php foreach ($categories as $catId => $catName):
                                    $value = $pivot[$ym][$catId] ?? 0;
                                    $heatClass = getHeatClass($value, $maxPerCategory[$catId] ?? 0);
                                    ?>
                                    <td class="category-col <?= $heatClass ?>"
                                        data-cat-id="<?= Html::encode($catId) ?>">
                                        <?php if ($value > 0): ?>
                                            <span class="cell-value">
                                                <?= Yii::$app->formatter->asDecimal($value, 0) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cell-value zero">-</span>
                                        <?php endif ?>
                                    </td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>

                    <!-- Table Footer -->
                    <tfoot>
                        <!-- Category Totals -->
                        <tr>
                            <td class="text-start">
                                <?= Yii::t('app', 'Category Totals') ?>
                            </td>
                            <?php foreach ($categories as $catId => $catName): ?>
                                <td class="category-col"
                                    data-cat-id="<?= Html::encode($catId) ?>">
                                    <span class="cell-value">
                                        <?= isset($totals[$catId]) ? Yii::$app->formatter->asDecimal($totals[$catId], 0) : '-' ?>
                                    </span>
                                </td>
                            <?php endforeach ?>
                        </tr>

                        <!-- Grand Total -->
                        <tr class="grand-total">
                            <td class="text-start">
                                <?= Yii::t('app', 'Grand Total') ?>
                            </td>
                            <td class="text-start" colspan="<?= $categoryCount ?>">
                                <?= Yii::$app->currency->format($grandTotal) ?>
                            </td>
                        </tr>
                    </tfoot>

                </table>
            </div>

            <!-- ── Card Footer ── -->
            <div class="card-footer">
                <div class="d-flex gap-3">
                    <span class="footer-stat">
                        <i class="bi bi-calendar3"></i>
                        <?= Yii::t('app', '{count} months', ['count' => count($months)]) ?>
                    </span>
                    <span class="footer-stat">
                        <i class="bi bi-tags"></i>
                        <?= Yii::t('app', '{count} categories', ['count' => $categoryCount]) ?>
                    </span>
                </div>
                <span class="footer-stat">
                    <i class="bi bi-clock-history"></i>
                    <?= Yii::t('app', 'Updated: {time}', [
                        'time' => Yii::$app->formatter->asDatetime(time(), 'short'),
                    ]) ?>
                </span>
            </div>
        <?php endif ?>

    </div>
</div>
<!-- End Fiscal Year Expense Summary Widget -->
