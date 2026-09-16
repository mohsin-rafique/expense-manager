<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * FBR Return Reconciliation View
 *
 * Compares the Personal Expenses declared on an uploaded FBR return against the
 * workspace's expenses for the same fiscal year, grouped by FBR tax category.
 *
 * @var yii\web\View $this
 * @var app\models\FbrReturnForm $model
 * @var array|null $parsed Parsed return (see FbrReturnParser::parse())
 * @var array|null $result Reconciliation result (see FbrReconciliationService::reconcile())
 * @var array|null $fiscalYear Fiscal year compared against
 * @var array $fiscalYears Available fiscal years for the selector
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */

use app\services\FbrReconciliationService;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'FBR Return Reconciliation');
$this->params['breadcrumbs'][] = $this->title;

$fmt = Yii::$app->formatter;
$statusMeta = FbrReconciliationService::statusMeta();

// The return prints whole rupees, so a category holding hundreds of expenses is
// routinely off by the sum of their rounded fractions. Anything within this is
// reported as a match.
$tolerance = FbrReconciliationService::TOLERANCE;

/**
 * Link to the expense list, filtered to one FBR tax category over the fiscal
 * year that was compared, so every figure below can be drilled into.
 *
 * @var callable(string): string
 */
$drill = static function (string $category) use ($fiscalYear): string {
    $params = ['/expense/index', 'ExpenseSearch[fbr_category]' => $category];

    if ($fiscalYear !== null) {
        $params['ExpenseSearch[start_date]'] = $fiscalYear['startDate'];
        $params['ExpenseSearch[end_date]'] = $fiscalYear['endDate'];
    }

    return Url::to($params);
};
?>

<!-- ============================================================== -->
<!-- Header                                                         -->
<!-- ============================================================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="recon-header">
        <div class="recon-header__icon">
            <i class="bi bi-file-earmark-bar-graph"></i>
        </div>
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Check the Personal Expenses you filed with FBR against your recorded expenses by FBR tax category.') ?>
            </p>
        </div>
    </div>

    <a class="recon-help" data-bs-toggle="collapse" href="#fbrHelp" role="button" aria-expanded="false">
        <span class="recon-help__icon"><i class="bi bi-question-lg"></i></span>
        <span>
            <span class="d-block fw-semibold small"><?= Yii::t('app', 'Need Help?') ?></span>
            <span class="d-block text-primary small"><?= Yii::t('app', 'Learn how this comparison works') ?></span>
        </span>
        <i class="bi bi-chevron-right text-muted ms-2"></i>
    </a>
</div>

<div class="collapse mb-4" id="fbrHelp">
    <div class="alert alert-info mb-0">
        <h6 class="fw-semibold"><i class="bi bi-info-circle me-1"></i><?= Yii::t('app', 'How this comparison works') ?></h6>
        <ol class="small mb-2 ps-3">
            <li><?= Yii::t('app', 'Upload the return PDF you downloaded from IRIS (for example "114(1) Return of Income filed voluntarily for complete year").') ?></li>
            <li><?= Yii::t('app', 'Its Wealth Statement Personal Expenses block is read: every line, its FBR code and the amount declared.') ?></li>
            <li><?= Yii::t('app', 'Your expenses for the fiscal year the return covers are summed by FBR tax category and rolled up to the same lines.') ?></li>
            <li><?= Yii::t('app', 'Each line is compared, so you can see exactly where the filed figure and your records disagree.') ?></li>
            <li><?= Yii::t('app', 'Nothing is written to the database - the return is only read and compared.') ?></li>
        </ol>
        <p class="small mb-0 text-muted">
            <i class="bi bi-dot"></i>
            <?= Yii::t('app', 'Car and plot installments are left out of the comparison: the return accounts for them under assets and liabilities, not personal expenses. They are listed separately below.') ?>
        </p>
    </div>
</div>

<div class="row g-4">
    <!-- ========================================================== -->
    <!-- Return Upload                                              -->
    <!-- ========================================================== -->
    <div class="col-lg-4">
        <div class="card recon-card">
            <div class="recon-card__header">
                <span class="recon-card__icon"><i class="bi bi-upload"></i></span>
                <h5 class="mb-0 fw-semibold"><?= Yii::t('app', 'Return Upload') ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= Yii::t('app', 'Upload the return exactly as IRIS generated it. The fiscal year is taken from the return\'s own period, so the comparison covers the year you filed.') ?>
                </div>

                <?php $form = ActiveForm::begin([
                    'options' => ['enctype' => 'multipart/form-data'],
                ]); ?>

                <!-- Return PDF dropzone -->
                <label class="form-label small fw-medium mb-1"><?= Yii::t('app', 'FBR return PDF') ?></label>
                <div class="recon-dropzone p-4 text-center" id="fbrDropzone">
                    <div id="fbrPlaceholder">
                        <i class="bi bi-filetype-pdf text-primary d-block" style="font-size: 2.25rem;"></i>
                        <div class="mt-2"><?= Yii::t('app', 'Drag & drop your return PDF here') ?></div>
                        <div class="text-muted small my-1"><?= Yii::t('app', 'or') ?></div>
                        <span class="btn btn-sm btn-outline-secondary"><?= Yii::t('app', 'Browse Files') ?></span>
                    </div>
                    <div id="fbrPicked" class="d-none d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-file-earmark-text text-success" style="font-size: 1.5rem;"></i>
                        <span class="fw-medium text-truncate" id="fbrFileName"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="fbrRemove" title="<?= Yii::t('app', 'Remove') ?>">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <?= Html::activeFileInput($model, 'file', [
                        'id' => 'fbrFileInput',
                        'accept' => '.pdf',
                        'class' => 'd-none',
                    ]) ?>
                </div>
                <div class="form-text small"><?= Yii::t('app', 'PDF only (max 10 MB).') ?></div>
                <?php if ($model->hasErrors('file')): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($model->getFirstError('file')) ?></div>
                <?php endif; ?>

                <!-- Fiscal year override -->
                <label class="form-label small fw-medium mb-1 mt-3" for="fbrFiscalYear">
                    <i class="bi bi-calendar-range me-1"></i><?= Yii::t('app', 'Fiscal year') ?>
                </label>
                <?= Html::activeDropDownList(
                    $model,
                    'fiscalYear',
                    ArrayHelper::map($fiscalYears, 'label', 'label'),
                    [
                        'id' => 'fbrFiscalYear',
                        'class' => 'form-select form-select-sm',
                        'prompt' => Yii::t('app', 'Detect from the return'),
                    ]
                ) ?>
                <div class="form-text small"><?= Yii::t('app', 'Only needed when the return\'s period line cannot be read.') ?></div>
                <?php if ($model->hasErrors('fiscalYear')): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($model->getFirstError('fiscalYear')) ?></div>
                <?php endif; ?>

                <!-- Optional open password for protected PDFs -->
                <label class="form-label small fw-medium mb-1 mt-3" for="fbrPassword">
                    <i class="bi bi-lock me-1"></i><?= Yii::t('app', 'PDF password (if protected)') ?>
                </label>
                <?= Html::activePasswordInput($model, 'password', [
                    'id' => 'fbrPassword',
                    'class' => 'form-control form-control-sm',
                    'autocomplete' => 'off',
                    'placeholder' => Yii::t('app', 'Leave blank for unprotected PDFs'),
                ]) ?>
                <div class="form-text small"><?= Yii::t('app', 'Only used to open the return - it is never saved or logged.') ?></div>
                <?php if ($model->hasErrors('password')): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($model->getFirstError('password')) ?></div>
                <?php endif; ?>

                <div class="d-grid mt-3">
                    <?= Html::submitButton(
                        '<i class="bi bi-search me-2"></i>' . Yii::t('app', 'Compare With My Expenses'),
                        ['class' => 'btn recon-submit']
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>

                <?php if ($parsed !== null): ?>
                    <!-- Return details, straight from the filed document -->
                    <hr class="my-3">
                    <h6 class="small fw-semibold text-uppercase text-muted mb-2">
                        <?= Yii::t('app', 'Return details') ?>
                    </h6>
                    <dl class="fbr-meta small mb-0">
                        <?php if (!empty($parsed['taxpayer'])): ?>
                            <dt><?= Yii::t('app', 'Taxpayer') ?></dt>
                            <dd><?= Html::encode($parsed['taxpayer']) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($parsed['registrationNo'])): ?>
                            <dt><?= Yii::t('app', 'Registration No') ?></dt>
                            <dd><?= Html::encode($parsed['registrationNo']) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($parsed['taxYear'])): ?>
                            <dt><?= Yii::t('app', 'Tax year') ?></dt>
                            <dd><?= Html::encode($parsed['taxYear']) ?></dd>
                        <?php endif; ?>
                        <?php if (!empty($parsed['periodStart']) && !empty($parsed['periodEnd'])): ?>
                            <dt><?= Yii::t('app', 'Period') ?></dt>
                            <dd><?= Html::encode($parsed['periodStart'] . ' to ' . $parsed['periodEnd']) ?></dd>
                        <?php endif; ?>
                        <?php if ($fiscalYear !== null): ?>
                            <dt><?= Yii::t('app', 'Compared against') ?></dt>
                            <dd>
                                <?= Html::encode($fiscalYear['label']) ?>
                                <?php if (empty($fiscalYear['fromReturn'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary fw-normal"><?= Yii::t('app', 'chosen') ?></span>
                                <?php endif; ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- Comparison Results                                         -->
    <!-- ========================================================== -->
    <div class="col-lg-8">
        <div class="card recon-card h-100">
            <div class="recon-card__header">
                <span class="recon-card__icon"><i class="bi bi-clipboard-data"></i></span>
                <h5 class="mb-0 fw-semibold"><?= Yii::t('app', 'Personal Expenses Comparison') ?></h5>
            </div>
            <div class="card-body">
                <?php if ($result === null): ?>
                    <div class="recon-empty">
                        <div class="recon-empty__badge"><i class="bi bi-file-earmark-bar-graph"></i></div>
                        <h5 class="fw-semibold"><?= Yii::t('app', 'No return compared yet') ?></h5>
                        <p class="text-muted mb-0">
                            <?= Yii::t('app', 'Upload your FBR return to see how each declared expense line compares with your records.') ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php
                    $difference = $result['difference'];
                    $inBalance = abs($difference) <= $tolerance;
                    $diffTone = $inBalance ? 'success' : 'danger';
                    ?>

                    <!-- Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="profile-stat-card">
                                <div class="profile-stat-icon bg-primary-subtle">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                </div>
                                <div class="profile-stat-content">
                                    <span class="profile-stat-value text-primary"><?= $fmt->asDecimal($result['declaredTotal'], 0) ?></span>
                                    <span class="profile-stat-label"><?= Yii::t('app', 'Declared on return') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="profile-stat-card">
                                <div class="profile-stat-icon bg-info-subtle">
                                    <i class="bi bi-journal-text text-info"></i>
                                </div>
                                <div class="profile-stat-content">
                                    <span class="profile-stat-value text-info"><?= $fmt->asDecimal($result['recordedTotal'], 0) ?></span>
                                    <span class="profile-stat-label"><?= Yii::t('app', 'Recorded in app') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="profile-stat-card">
                                <div class="profile-stat-icon bg-<?= $diffTone ?>-subtle">
                                    <i class="bi bi-<?= $inBalance ? 'check2-circle' : 'exclamation-triangle' ?> text-<?= $diffTone ?>"></i>
                                </div>
                                <div class="profile-stat-content">
                                    <span class="profile-stat-value text-<?= $diffTone ?>">
                                        <?= ($difference > 0 ? '+' : '') . $fmt->asDecimal($difference, 0) ?>
                                    </span>
                                    <span class="profile-stat-label"><?= Yii::t('app', 'Difference') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($inBalance): ?>
                        <div class="alert alert-success small">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= Yii::t('app', 'Your recorded expenses agree with the Personal Expenses declared on the return.') ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-<?= $difference > 0 ? 'warning' : 'danger' ?> small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <?= $difference > 0
                                ? Yii::t('app', 'You recorded {amount} more than the return declares. Review the lines marked below.', ['amount' => $fmt->asDecimal(abs($difference), 2)])
                                : Yii::t('app', 'The return declares {amount} more than you recorded. Expenses may be missing from the app.', ['amount' => $fmt->asDecimal(abs($difference), 2)]) ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // The detail lines should add up to the Personal Expenses total
                    // printed on the return. If they do not, a line was missed while
                    // reading the PDF and every figure below is suspect.
                    $returnedTotal = $result['returnedTotal'];
                    $lineSum = $result['declaredTotal'];
                    ?>
                    <?php if ($returnedTotal !== null && abs($returnedTotal - $lineSum) > $tolerance): ?>
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <?= Yii::t('app', 'The return declares {total} as Personal Expenses but its lines add up to {sum}. Some lines may not have been read correctly.', [
                                'total' => $fmt->asDecimal($returnedTotal, 2),
                                'sum' => $fmt->asDecimal($lineSum, 2),
                            ]) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Per-line comparison -->
                    <div class="table-responsive">
                        <table class="table table-sm align-middle fbr-table mb-0">
                            <thead>
                                <tr>
                                    <th><?= Yii::t('app', 'Code') ?></th>
                                    <th><?= Yii::t('app', 'Wealth statement line') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Declared') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Recorded') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Difference') ?></th>
                                    <th><?= Yii::t('app', 'Status') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['rows'] as $i => $row):
                                    $meta = $statusMeta[$row['status']];
                                    $rowId = 'fbr-break-' . $row['code'] . '-' . $i;
                                    $hasBreakdown = count($row['breakdown']) > 0;
                                    ?>
                                    <tr class="fbr-row fbr-row--<?= $meta['tone'] ?>">
                                        <td class="text-muted small text-nowrap"><?= Html::encode($row['code']) ?></td>
                                        <td>
                                            <?php if ($hasBreakdown): ?>
                                                <button class="btn btn-link btn-sm p-0 text-start text-decoration-none fbr-toggle"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#<?= $rowId ?>" aria-expanded="false">
                                                    <i class="bi bi-chevron-right fbr-toggle__chevron me-1"></i>
                                                    <?= Html::encode($row['label']) ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="ps-3"><?= Html::encode($row['label']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!$row['onReturn']): ?>
                                                <span class="badge bg-danger-subtle text-danger fw-normal ms-1">
                                                    <?= Yii::t('app', 'absent from return') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-nowrap"><?= $fmt->asDecimal($row['declared'], 0) ?></td>
                                        <td class="text-end text-nowrap"><?= $fmt->asDecimal($row['recorded'], 2) ?></td>
                                        <td class="text-end text-nowrap fw-semibold text-<?= $row['status'] === FbrReconciliationService::STATUS_MATCH ? 'muted' : $meta['tone'] ?>">
                                            <?= ($row['difference'] > 0 ? '+' : '') . $fmt->asDecimal($row['difference'], 2) ?>
                                        </td>
                                        <td class="text-nowrap">
                                            <span class="badge bg-<?= $meta['tone'] ?>-subtle text-<?= $meta['tone'] ?> fw-normal"
                                                title="<?= Html::encode($meta['hint']) ?>">
                                                <i class="bi <?= $meta['icon'] ?> me-1"></i><?= Html::encode($meta['label']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if ($hasBreakdown): ?>
                                        <tr class="fbr-breakdown-row">
                                            <td colspan="6" class="p-0 border-0">
                                                <div class="collapse" id="<?= $rowId ?>">
                                                    <div class="fbr-breakdown">
                                                        <div class="small text-muted mb-2">
                                                            <?= Yii::t('app', 'Your expense categories rolled up into this line:') ?>
                                                        </div>
                                                        <table class="table table-sm mb-0">
                                                            <tbody>
                                                                <?php foreach ($row['breakdown'] as $part): ?>
                                                                    <tr>
                                                                        <td class="small">
                                                                            <a href="<?= $drill($part['code']) ?>">
                                                                                <?= Html::encode($part['label']) ?>
                                                                            </a>
                                                                        </td>
                                                                        <td class="text-end small text-nowrap"><?= $fmt->asDecimal($part['amount'], 2) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fbr-total">
                                    <td></td>
                                    <td class="fw-semibold"><?= Yii::t('app', 'Personal Expenses') ?></td>
                                    <td class="text-end fw-semibold text-nowrap"><?= $fmt->asDecimal($result['declaredTotal'], 0) ?></td>
                                    <td class="text-end fw-semibold text-nowrap"><?= $fmt->asDecimal($result['recordedTotal'], 2) ?></td>
                                    <td class="text-end fw-semibold text-nowrap text-<?= $diffTone ?>">
                                        <?= ($difference > 0 ? '+' : '') . $fmt->asDecimal($difference, 2) ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= Yii::t('app', 'The return is filed in whole rupees, so differences up to {tolerance} are treated as a match.', [
                            'tolerance' => $fmt->asDecimal($tolerance, 2),
                        ]) ?>
                    </p>

                    <?php if (!empty($result['unmapped'])): ?>
                        <!-- Return lines this app has no category for -->
                        <h6 class="fw-semibold mt-4 mb-2">
                            <i class="bi bi-question-circle me-1 text-muted"></i>
                            <?= Yii::t('app', 'Declared lines with no matching category') ?>
                        </h6>
                        <p class="small text-muted mb-2">
                            <?= Yii::t('app', 'The return declares these lines under codes this app does not map to an FBR tax category, so they are not part of the comparison above.') ?>
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <?php foreach ($result['unmapped'] as $line): ?>
                                        <tr>
                                            <td class="text-muted small text-nowrap"><?= Html::encode($line['code']) ?></td>
                                            <td class="small"><?= Html::encode($line['label']) ?></td>
                                            <td class="text-end small text-nowrap"><?= $fmt->asDecimal($line['amount'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($result['excluded'])): ?>
                        <!-- Recorded, but not personal expenses on the return -->
                        <h6 class="fw-semibold mt-4 mb-2">
                            <i class="bi bi-box-arrow-right me-1 text-muted"></i>
                            <?= Yii::t('app', 'Recorded but outside personal expenses') ?>
                        </h6>
                        <p class="small text-muted mb-2">
                            <?= Yii::t('app', 'These buy an asset or repay a liability, so the return accounts for them in the Wealth Statement\'s assets and liabilities rather than in Personal Expenses. They are excluded from the comparison above.') ?>
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <?php foreach ($result['excluded'] as $part): ?>
                                        <tr>
                                            <td class="small">
                                                <a href="<?= $drill($part['code']) ?>">
                                                    <?= Html::encode($part['label']) ?>
                                                </a>
                                            </td>
                                            <td class="text-end small text-nowrap"><?= $fmt->asDecimal($part['amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="fbr-total">
                                        <td class="small fw-semibold"><?= Yii::t('app', 'Total') ?></td>
                                        <td class="text-end small fw-semibold text-nowrap"><?= $fmt->asDecimal($result['excludedTotal'], 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= Url::to(['/site/index']) ?>">
                            <i class="bi bi-table me-1"></i>
                            <?= Yii::t('app', 'Open the FBR tax category summary') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$css = require __DIR__ . '/_styles.php';

$css .= <<<'CSS'

/* Return details list */
.fbr-meta { display: grid; grid-template-columns: auto 1fr; gap: 0.35rem 1rem; margin: 0; }
.fbr-meta dt { color: var(--em-gray-500); font-weight: 500; }
.fbr-meta dd { margin: 0; color: var(--em-gray-800); word-break: break-word; }

/* Comparison table */
.fbr-table th { white-space: nowrap; font-size: 0.8125rem; color: var(--em-gray-500); font-weight: 600; }
.fbr-table > tbody > tr.fbr-row > td { border-bottom-color: var(--em-gray-100); }
.fbr-row--warning > td:first-child { box-shadow: inset 3px 0 0 var(--bs-warning); }
.fbr-row--danger > td:first-child { box-shadow: inset 3px 0 0 var(--bs-danger); }
.fbr-breakdown-row > td { background: transparent; }
.fbr-breakdown {
    padding: 0.75rem 1rem 0.75rem 2.25rem;
    background: var(--em-gray-50); border-radius: var(--em-radius-md); margin-bottom: 0.5rem;
}
.fbr-breakdown .table { background: transparent; }
.fbr-toggle { color: inherit; }
.fbr-toggle:hover { color: var(--em-primary); }
.fbr-toggle__chevron { transition: transform var(--em-transition); display: inline-block; font-size: 0.7rem; }
.fbr-toggle[aria-expanded="true"] .fbr-toggle__chevron { transform: rotate(90deg); }
.fbr-total > td { background: var(--em-gray-50); border-top: 2px solid var(--em-gray-200); }
CSS;
$this->registerCss($css);

$js = <<<'JS'
(function () {
    var dz = document.getElementById('fbrDropzone');
    var input = document.getElementById('fbrFileInput');
    var placeholder = document.getElementById('fbrPlaceholder');
    var picked = document.getElementById('fbrPicked');
    var nameEl = document.getElementById('fbrFileName');
    if (!dz || !input) { return; }

    function showFile(file) {
        if (!file) { return; }
        nameEl.textContent = file.name;
        placeholder.classList.add('d-none');
        picked.classList.remove('d-none');
    }
    function resetFile() {
        input.value = '';
        picked.classList.add('d-none');
        placeholder.classList.remove('d-none');
    }

    dz.addEventListener('click', function (e) {
        if (e.target.closest('#fbrRemove')) { return; }
        input.click();
    });
    input.addEventListener('change', function () {
        if (input.files && input.files.length) { showFile(input.files[0]); }
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
        dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('dragover'); });
    });
    ['dragleave', 'dragend'].forEach(function (ev) {
        dz.addEventListener(ev, function () { dz.classList.remove('dragover'); });
    });
    dz.addEventListener('drop', function (e) {
        e.preventDefault();
        dz.classList.remove('dragover');
        var files = e.dataTransfer && e.dataTransfer.files;
        if (files && files.length) {
            try {
                input.files = files;
            } catch (err) {
                var dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
            }
            showFile(files[0]);
        }
    });
    var removeBtn = document.getElementById('fbrRemove');
    if (removeBtn) {
        removeBtn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); resetFile(); });
    }
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
