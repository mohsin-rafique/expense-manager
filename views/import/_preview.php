<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Import Preview Partial
 *
 * Renders the validated rows with per-row status before the user confirms the
 * import. Returned as HTML inside the preview AJAX response.
 *
 * @var yii\web\View $this
 * @var string $type Import type ('expense' | 'income')
 * @var array $rows Validated rows from ImportService::validateRows()
 * @var array $summary Summary counts
 * @var bool $isStatement Whether the upload was a bank statement
 * @var string|null $format Detected statement format, when applicable
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use app\models\ExpenseCategory;
use app\services\BankStatementParser;

$cur = Yii::$app->currency;

// The classifier stores FBR codes (OTHER_PERS, TELEPHONE, ...). Those are
// storage keys, not something to show a user, so map them to their labels.
// The codes are a Pakistan tax concept, matching how the expense form gates
// its own FBR field, so only show them to PK profiles.
$fbrLabels = ExpenseCategory::getFbrCategories();
$showFbr = (Yii::$app->user->identity?->profile?->country_code) === 'PK';
$isExpense = $type === \app\services\ImportService::TYPE_EXPENSE;
$isStatement = $isStatement ?? false;
$format = $format ?? null;
$maxShown = 200;
$shown = array_slice($rows, 0, $maxShown);
$excludedCount = (int) ($summary['excluded'] ?? 0);

// Group the excluded rows by reason so the user can see at a glance what was
// left out and for how much, rather than scanning the table for greyed rows.
$excludedGroups = [];
foreach ($rows as $r) {
    if (!empty($r['excluded'])) {
        $excludedGroups[$r['excluded']]['count'] = ($excludedGroups[$r['excluded']]['count'] ?? 0) + 1;
        $excludedGroups[$r['excluded']]['total'] = ($excludedGroups[$r['excluded']]['total'] ?? 0) + (float) $r['amount'];
    }
}
?>

<?php if ($isStatement && $format !== null): ?>
    <div class="alert alert-info py-2 small d-flex align-items-center gap-2">
        <i class="bi bi-bank"></i>
        <div>
            <?= Html::encode(BankStatementParser::formatList()[$format] ?? $format) ?>
            <span class="text-muted">
                &middot; <?= Yii::t('app', 'Rows import as drafts so you can review them in the expense list. Drafts still count in dashboard totals and budgets.') ?>
            </span>
        </div>
    </div>
<?php endif; ?>

<!-- Summary chips -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge bg-secondary-subtle text-secondary">
        <?= Yii::t('app', 'Total') ?>: <?= (int) $summary['total'] ?>
    </span>
    <span class="badge bg-success-subtle text-success">
        <i class="bi bi-check-circle me-1"></i><?= Yii::t('app', 'Importable') ?>: <?= (int) $summary['importable'] ?>
    </span>
    <?php if ($summary['duplicates'] > 0): ?>
        <span class="badge bg-warning-subtle text-warning">
            <i class="bi bi-files me-1"></i><?= Yii::t('app', 'Duplicates') ?>: <?= (int) $summary['duplicates'] ?>
        </span>
    <?php endif; ?>
    <?php if ($summary['invalid'] > 0): ?>
        <span class="badge bg-danger-subtle text-danger">
            <i class="bi bi-x-circle me-1"></i><?= Yii::t('app', 'Invalid') ?>: <?= (int) $summary['invalid'] ?>
        </span>
    <?php endif; ?>
    <?php if ($excludedCount > 0): ?>
        <span class="badge bg-secondary-subtle text-secondary">
            <i class="bi bi-slash-circle me-1"></i><?= Yii::t('app', 'Not spending') ?>: <?= $excludedCount ?>
        </span>
    <?php endif; ?>
    <?php if (!empty($summary['newCategories'])): ?>
        <span class="badge bg-info-subtle text-info">
            <i class="bi bi-folder-plus me-1"></i><?= Yii::t('app', 'New categories') ?>: <?= count($summary['newCategories']) ?>
        </span>
    <?php endif; ?>
</div>

<?php if ($summary['invalid'] > 0): ?>
    <div class="alert alert-warning py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= Yii::t('app', 'Invalid rows will be skipped. Fix them in your file and re-upload to include them.') ?>
    </div>
<?php endif; ?>

<?php if (!empty($excludedGroups)): ?>
    <div class="alert alert-secondary py-2 small">
        <div class="fw-semibold mb-1">
            <i class="bi bi-slash-circle me-1"></i><?= Yii::t('app', 'Left out: money moved rather than spent') ?>
        </div>
        <ul class="mb-1 ps-3">
            <?php foreach ($excludedGroups as $reason => $g): ?>
                <li>
                    <?= Html::encode($reason) ?>:
                    <?= Yii::t('app', '{count} row(s)', ['count' => $g['count']]) ?>
                    &middot; <?= Html::encode($cur->format($g['total'])) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="text-muted">
            <?= Yii::t('app', 'Turn on "Include cash withdrawals and account transfers" and preview again to import these.') ?>
        </div>
    </div>
<?php endif; ?>

<?php
// Fixed layout with explicit widths, so the seven columns always divide the
// panel instead of adding up past it and forcing a horizontal scrollbar. The
// long statement descriptions then wrap inside their cell.
?>
<style>
    .import-preview { table-layout: fixed; width: 100%; }
    .import-preview td, .import-preview th { overflow-wrap: anywhere; word-break: break-word; }
</style>

<div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
    <table class="table table-sm table-hover align-middle mb-0 import-preview">
        <thead class="table-light position-sticky top-0">
            <tr>
                <th style="width: 3rem;">#</th>
                <th style="width: 5.5rem;"><?= Yii::t('app', 'Status') ?></th>
                <th style="width: 6rem;"><?= Yii::t('app', 'Date') ?></th>
                <th style="width: 20%;"><?= Yii::t('app', 'Category') ?></th>
                <?php if ($isExpense): ?><th style="width: 4.5rem;"><?= Yii::t('app', 'Payment') ?></th><?php endif; ?>
                <th class="text-end" style="width: 6.5rem;"><?= Yii::t('app', 'Amount') ?></th>
                <th><?= Yii::t('app', 'Details') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shown as $r): ?>
                <?php
                $excluded = $r['excluded'] ?? null;
                if ($excluded !== null) {
                    $rowClass = 'text-muted opacity-75';
                } elseif (!$r['valid']) {
                    $rowClass = 'table-danger';
                } elseif ($r['duplicate']) {
                    $rowClass = 'table-warning';
                } else {
                    $rowClass = '';
                }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="text-muted small"><?= (int) $r['line'] ?></td>
                    <td>
                        <?php if ($excluded !== null): ?>
                            <span class="badge bg-secondary" title="<?= Html::encode($excluded) ?>"><?= Yii::t('app', 'Not spending') ?></span>
                        <?php elseif (!$r['valid']): ?>
                            <span class="badge bg-danger"><?= Yii::t('app', 'Skip') ?></span>
                        <?php elseif ($r['duplicate']): ?>
                            <span class="badge bg-warning text-dark"><?= Yii::t('app', 'Duplicate') ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= Yii::t('app', 'OK') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= Html::encode((string) $r['date']) ?></td>
                    <td class="small">
                        <?php if ($excluded !== null): ?>
                            <span class="fst-italic"><?= Html::encode($excluded) ?></span>
                        <?php else: ?>
                            <?= Html::encode((string) $r['category']) ?>
                            <?php if ($r['willCreateCategory']): ?>
                                <i class="bi bi-folder-plus text-info ms-1" title="<?= Yii::t('app', 'Will be created') ?>"></i>
                            <?php endif; ?>
                            <?php if ($showFbr && !empty($r['fbr'])): ?>
                                <?php $fbrLabel = $fbrLabels[$r['fbr']] ?? $r['fbr']; ?>
                                <div class="text-muted mt-1" style="font-size: .75rem;"
                                     title="<?= Html::encode(Yii::t('app', 'FBR tax category')) ?>">
                                    <i class="bi bi-receipt me-1"></i><?= Html::encode($fbrLabel) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php if ($isExpense): ?>
                        <td class="small"><?= Html::encode((string) $r['payment_method']) ?></td>
                    <?php endif; ?>
                    <td class="text-end small">
                        <?= $r['amount'] !== null ? Html::encode($cur->format($r['amount'])) : '<span class="text-danger">-</span>' ?>
                    </td>
                    <td class="small text-muted">
                        <?php if (!empty($r['errors'])): ?>
                            <span class="text-danger"><?= Html::encode(implode('; ', $r['errors'])) ?></span>
                        <?php else: ?>
                            <?= Html::encode((string) ($r['reference'] ?? '')) ?>
                            <?= $r['reference'] && $r['description'] ? ' · ' : '' ?>
                            <?= Html::encode((string) ($r['description'] ?? '')) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (count($rows) > $maxShown): ?>
    <p class="text-muted small mt-2 mb-0">
        <?= Yii::t('app', 'Showing first {shown} of {total} rows. All rows will be processed on import.', [
            'shown' => $maxShown,
            'total' => count($rows),
        ]) ?>
    </p>
<?php endif; ?>
