<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
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
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

$cur = Yii::$app->currency;
$isExpense = $type === \app\services\ImportService::TYPE_EXPENSE;
$maxShown = 200;
$shown = array_slice($rows, 0, $maxShown);
?>

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

<div class="table-responsive" style="max-height: 460px; overflow-y: auto;">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light position-sticky top-0">
            <tr>
                <th style="width: 48px;">#</th>
                <th><?= Yii::t('app', 'Status') ?></th>
                <th><?= Yii::t('app', 'Date') ?></th>
                <th><?= Yii::t('app', 'Category') ?></th>
                <?php if ($isExpense): ?><th><?= Yii::t('app', 'Payment') ?></th><?php endif; ?>
                <th class="text-end"><?= Yii::t('app', 'Amount') ?></th>
                <th><?= Yii::t('app', 'Details') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shown as $r): ?>
                <?php
                if (!$r['valid']) {
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
                        <?php if (!$r['valid']): ?>
                            <span class="badge bg-danger"><?= Yii::t('app', 'Skip') ?></span>
                        <?php elseif ($r['duplicate']): ?>
                            <span class="badge bg-warning text-dark"><?= Yii::t('app', 'Duplicate') ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= Yii::t('app', 'OK') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= Html::encode((string) $r['date']) ?></td>
                    <td class="small">
                        <?= Html::encode((string) $r['category']) ?>
                        <?php if ($r['willCreateCategory']): ?>
                            <i class="bi bi-folder-plus text-info ms-1" title="<?= Yii::t('app', 'Will be created') ?>"></i>
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
