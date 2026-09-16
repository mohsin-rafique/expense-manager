<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Bank Statement Reconciliation View
 *
 * @var yii\web\View $this
 * @var app\models\ReconcileForm $model
 * @var array|null $result Reconciliation result from ReconciliationService
 * @var int $parseSkipped Count of unparseable statement lines
 * @var int $reversedIgnored Count of reversed/refunded debits that were ignored
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */

use app\services\ReconciliationService;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Statement Reconciliation');
$this->params['breadcrumbs'][] = $this->title;

$categories = ReconciliationService::statementCategories();
$fmt = Yii::$app->formatter;
$canAdd = Yii::$app->workspace->can(\app\models\WorkspaceMember::CAN_MANAGE_DATA);
?>

<!-- ============================================================== -->
<!-- Header                                                         -->
<!-- ============================================================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="recon-header">
        <div class="recon-header__icon">
            <i class="bi bi-bank2"></i>
        </div>
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Compare your bank statement against recorded expenses to find entries that are missing.') ?>
            </p>
        </div>
    </div>

    <a class="recon-help" data-bs-toggle="collapse" href="#reconHelp" role="button" aria-expanded="false">
        <span class="recon-help__icon"><i class="bi bi-question-lg"></i></span>
        <span>
            <span class="d-block fw-semibold small"><?= Yii::t('app', 'Need Help?') ?></span>
            <span class="d-block text-primary small"><?= Yii::t('app', 'Learn how reconciliation works') ?></span>
        </span>
        <i class="bi bi-chevron-right text-muted ms-2"></i>
    </a>
</div>

<div class="collapse mb-4" id="reconHelp">
    <div class="alert alert-info mb-0">
        <h6 class="fw-semibold"><i class="bi bi-info-circle me-1"></i><?= Yii::t('app', 'How reconciliation works') ?></h6>
        <ol class="small mb-0 ps-3">
            <li><?= Yii::t('app', 'Upload your bank\'s CSV export or PDF statement (or paste the debit lines).') ?></li>
            <li><?= Yii::t('app', 'Each statement debit is matched against your expenses by its reference (STAN / transfer code).') ?></li>
            <li><?= Yii::t('app', 'Unmatched entries are grouped: genuinely missing expenses, plus cash withdrawals, bank fees and reversals that are usually not tracked.') ?></li>
            <li><?= Yii::t('app', 'Nothing is written to the database - it only reports what is missing.') ?></li>
        </ol>
    </div>
</div>

<div class="row g-4">
    <!-- ========================================================== -->
    <!-- Statement Input                                            -->
    <!-- ========================================================== -->
    <div class="col-lg-5">
        <div class="card recon-card">
            <div class="recon-card__header">
                <span class="recon-card__icon"><i class="bi bi-upload"></i></span>
                <h5 class="mb-0 fw-semibold"><?= Yii::t('app', 'Statement Input') ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= Yii::t('app', 'Upload your bank\'s CSV export or PDF statement directly - the Date, Description and Debit columns are detected automatically and only debit (expense) rows are compared.') ?>
                    <span class="d-block mt-2 text-muted">
                        <?= Yii::t('app', 'Or paste tab-separated lines:') ?>
                        <code>date&nbsp;&rarr;&nbsp;amount&nbsp;&rarr;&nbsp;description</code>
                    </span>
                </div>

                <?php $form = ActiveForm::begin([
                    'options' => ['enctype' => 'multipart/form-data'],
                ]); ?>

                <!-- Statement lines (code editor) -->
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label small fw-medium mb-0" for="statementArea">
                        <?= Yii::t('app', 'Statement lines (tab-separated)') ?>
                    </label>
                    <span class="badge bg-primary bg-opacity-10 text-primary" id="lineCountBadge">0 <?= Yii::t('app', 'lines') ?></span>
                </div>

                <div class="code-input-wrap position-relative mb-1">
                    <div class="code-input">
                        <div class="code-input__gutter" id="statementGutter">1
</div>
                        <?= Html::activeTextarea($model, 'statement', [
                            'id' => 'statementArea',
                            'class' => 'code-input__area',
                            'rows' => 8,
                            'spellcheck' => 'false',
                            'placeholder' => "2026-06-08\t42000\tAL-KABIR TOWN PRIVATE Online Purchase-STAN(932752)",
                        ]) ?>
                    </div>
                    <div class="dropdown code-input__menu">
                        <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= Yii::t('app', 'Options') ?>">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item" type="button" id="reconClear">
                                    <i class="bi bi-eraser me-2 text-muted"></i><?= Yii::t('app', 'Clear') ?>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" type="button" id="reconCopy">
                                    <i class="bi bi-clipboard me-2 text-muted"></i><?= Yii::t('app', 'Copy') ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php if ($model->hasErrors('statement')): ?>
                    <div class="text-danger small mb-2"><?= Html::encode($model->getFirstError('statement')) ?></div>
                <?php endif; ?>

                <!-- OR divider -->
                <div class="recon-or"><span><?= Yii::t('app', 'OR') ?></span></div>

                <!-- CSV / PDF dropzone -->
                <label class="form-label small fw-medium mb-1"><?= Yii::t('app', 'Upload statement file (CSV or PDF)') ?></label>
                <div class="recon-dropzone p-4 text-center" id="reconDropzone">
                    <div id="reconPlaceholder">
                        <i class="bi bi-cloud-arrow-up text-primary d-block" style="font-size: 2.25rem;"></i>
                        <div class="mt-2"><?= Yii::t('app', 'Drag & drop your CSV or PDF file here') ?></div>
                        <div class="text-muted small my-1"><?= Yii::t('app', 'or') ?></div>
                        <span class="btn btn-sm btn-outline-secondary"><?= Yii::t('app', 'Browse Files') ?></span>
                    </div>
                    <div id="reconPicked" class="d-none d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-file-earmark-text text-success" style="font-size: 1.5rem;"></i>
                        <span class="fw-medium text-truncate" id="reconFileName"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="reconRemove" title="<?= Yii::t('app', 'Remove') ?>">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <?= Html::activeFileInput($model, 'file', [
                        'id' => 'reconFileInput',
                        'accept' => '.csv,.tsv,.txt,.pdf',
                        'class' => 'd-none',
                    ]) ?>
                </div>
                <div class="form-text small"><?= Yii::t('app', 'CSV and PDF statements are supported (max 5 MB).') ?></div>
                <?php if ($model->hasErrors('file')): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($model->getFirstError('file')) ?></div>
                <?php endif; ?>

                <!-- Optional open password for protected PDFs -->
                <label class="form-label small fw-medium mb-1 mt-3" for="reconPassword">
                    <i class="bi bi-lock me-1"></i><?= Yii::t('app', 'PDF password (if protected)') ?>
                </label>
                <?= Html::activePasswordInput($model, 'password', [
                    'id' => 'reconPassword',
                    'class' => 'form-control form-control-sm',
                    'autocomplete' => 'off',
                    'placeholder' => Yii::t('app', 'Leave blank for CSV or unprotected PDFs'),
                ]) ?>
                <div class="form-text small"><?= Yii::t('app', 'Only used to open the statement - it is never saved or logged.') ?></div>
                <?php if ($model->hasErrors('password')): ?>
                    <div class="text-danger small mt-1"><?= Html::encode($model->getFirstError('password')) ?></div>
                <?php endif; ?>

                <div class="d-grid mt-3">
                    <?= Html::submitButton(
                        '<i class="bi bi-search me-2"></i>' . Yii::t('app', 'Reconcile'),
                        ['class' => 'btn recon-submit']
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- Reconciliation Results                                     -->
    <!-- ========================================================== -->
    <div class="col-lg-7">
        <div class="card recon-card h-100">
            <div class="recon-card__header">
                <span class="recon-card__icon"><i class="bi bi-clipboard-data"></i></span>
                <h5 class="mb-0 fw-semibold"><?= Yii::t('app', 'Reconciliation Results') ?></h5>
            </div>
            <div class="card-body">
                <?php if ($result === null): ?>
                    <div class="recon-empty">
                        <div class="recon-empty__badge"><i class="bi bi-clipboard-check"></i></div>
                        <h5 class="fw-semibold"><?= Yii::t('app', 'No reconciliation yet') ?></h5>
                        <p class="text-muted mb-0">
                            <?= Yii::t('app', 'Upload your bank statement or enter statement lines and click Reconcile to see results here.') ?>
                        </p>
                    </div>
                <?php else: ?>
                    <!-- Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="profile-stat-card">
                                <div class="profile-stat-icon bg-primary-subtle">
                                    <i class="bi bi-list-ol text-primary"></i>
                                </div>
                                <div class="profile-stat-content">
                                    <span class="profile-stat-value text-primary"><?= $fmt->asInteger($result['totalRows']) ?></span>
                                    <span class="profile-stat-label"><?= Yii::t('app', 'Statement lines') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="profile-stat-card">
                                <div class="profile-stat-icon bg-success-subtle">
                                    <i class="bi bi-check2-circle text-success"></i>
                                </div>
                                <div class="profile-stat-content">
                                    <span class="profile-stat-value text-success recon-stat-matched"><?= $fmt->asInteger($result['matched']) ?></span>
                                    <span class="profile-stat-label"><?= Yii::t('app', 'Matched') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="profile-stat-card">
                                <div class="profile-stat-icon bg-danger-subtle">
                                    <i class="bi bi-exclamation-triangle text-danger"></i>
                                </div>
                                <div class="profile-stat-content">
                                    <span class="profile-stat-value text-danger recon-stat-missing"><?= $fmt->asInteger($result['missingCount']) ?></span>
                                    <span class="profile-stat-label"><?= Yii::t('app', 'Not matched') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($reversedIgnored > 0): ?>
                        <div class="alert alert-secondary small">
                            <i class="bi bi-arrow-left-right me-1"></i>
                            <?= Yii::t('app', '{count} reversed/refunded transaction(s) were ignored.', ['count' => $reversedIgnored]) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($parseSkipped > 0): ?>
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <?= Yii::t('app', '{count} line(s) could not be parsed and were skipped.', ['count' => $parseSkipped]) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Per-category tabs (each mapped to the SC Schedule of Charges) -->
                    <?php
                    $cats = $result['categories'];
                    $catTotals = $result['categoryTotals'];
                    $activeKeys = [];
                    foreach ($categories as $key => $meta) {
                        if (!empty($cats[$key])) {
                            $activeKeys[] = $key;
                        }
                    }
                    $firstKey = $activeKeys[0] ?? null;
                    ?>

                    <?php if (empty($activeKeys)): ?>
                        <div class="text-center py-4 text-success">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= Yii::t('app', 'Nothing missing - every statement line is accounted for.') ?>
                        </div>
                    <?php else: ?>
                        <ul class="nav nav-tabs recon-tabs flex-nowrap" role="tablist">
                            <?php foreach ($activeKeys as $key):
                                $meta = $categories[$key];
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $key === $firstKey ? 'active' : '' ?>" id="tabbtn-<?= $key ?>"
                                        data-bs-toggle="tab" data-bs-target="#tab-<?= $key ?>" type="button" role="tab">
                                        <i class="bi <?= $meta['icon'] ?> me-1"></i>
                                        <?= Html::encode($meta['label']) ?>
                                        <span class="badge rounded-pill bg-<?= $meta['tone'] ?>-subtle text-<?= $meta['tone'] ?> ms-1"><?= count($cats[$key]) ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tab-content pt-3">
                            <?php foreach ($activeKeys as $key):
                                $meta = $categories[$key];
                                $rows = $cats[$key];
                                $actionable = !empty($meta['actionable']) && $canAdd;
                                ?>
                                <div class="tab-pane fade <?= $key === $firstKey ? 'show active' : '' ?>" id="tab-<?= $key ?>" role="tabpanel">
                                    <!-- Official SC Schedule of Charges mapping -->
                                    <div class="recon-soc alert alert-<?= $meta['tone'] === 'danger' ? 'danger' : 'secondary' ?> bg-opacity-10 small d-flex align-items-start">
                                        <i class="bi bi-info-circle me-2 mt-1"></i>
                                        <span><?= Html::encode($meta['soc']) ?></span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted">
                                            <?= Yii::t('app', '{count} line(s)', ['count' => Html::tag('span', count($rows), ['class' => 'recon-line-count'])]) ?>
                                        </span>
                                        <span class="fw-semibold recon-subtotal"><?= $fmt->asDecimal($catTotals[$key], 2) ?></span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th><?= Yii::t('app', 'Date') ?></th>
                                                    <th class="text-end"><?= Yii::t('app', 'Amount') ?></th>
                                                    <th><?= Yii::t('app', 'Description') ?></th>
                                                    <?php if ($actionable): ?>
                                                        <th class="text-end"><?= Yii::t('app', 'Action') ?></th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $r): ?>
                                                    <tr data-amount="<?= Html::encode((string) $r['amount']) ?>">
                                                        <td class="text-nowrap small"><?= Html::encode($r['date']) ?></td>
                                                        <td class="text-end text-nowrap fw-semibold"><?= $fmt->asDecimal($r['amount'], 2) ?></td>
                                                        <td>
                                                            <div class="small<?= $actionable ? '' : ' text-muted' ?>"><?= Html::encode($r['description']) ?></div>
                                                            <?php foreach ($r['flags'] as $flag): ?>
                                                                <div class="badge bg-warning bg-opacity-10 text-warning fw-normal">
                                                                    <i class="bi bi-info-circle me-1"></i><?= Html::encode($flag) ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </td>
                                                        <?php if ($actionable): ?>
                                                            <td class="text-end">
                                                                <?= Html::button(
                                                                    '<i class="bi bi-plus-lg"></i>',
                                                                    [
                                                                        'class' => 'btn btn-sm btn-outline-danger btn-modal',
                                                                        'title' => Yii::t('app', 'Add as expense'),
                                                                        'data-url' => Url::to(array_merge([
                                                                            '/expense/create',
                                                                            'expense_date' => $r['date'],
                                                                            'amount' => $r['amount'],
                                                                            'reference' => $r['description'],
                                                                            'source' => 'reconcile',
                                                                        ], !empty($meta['fbr']) ? ['fbr_category' => $meta['fbr']] : [])),
                                                                        'data-title' => Yii::t('app', 'Add New Expense'),
                                                                        'data-icon' => '<i class="bi bi-graph-down-arrow text-danger me-2"></i>',
                                                                        'data-target' => '#nemModal',
                                                                    ]
                                                                ) ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($result['latestDbDate'] !== null): ?>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="bi bi-clock-history me-1"></i>
                            <?= Yii::t('app', 'Your latest recorded expense is dated {date}.', ['date' => $result['latestDbDate']]) ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Header, card, dropzone, submit and empty-state rules are shared with the FBR
// return reconciliation screen; only this page's own rules follow.
$css = require __DIR__ . '/_styles.php';

$css .= <<<'CSS'

/* Code-style statement input */
.code-input {
    display: flex; overflow: hidden;
    border: 1px solid var(--em-gray-300); border-radius: var(--em-radius-md);
    background: var(--em-gray-50);
}
.code-input:focus-within {
    border-color: var(--em-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--em-primary-rgb), 0.15);
}
.code-input__gutter {
    flex-shrink: 0; min-width: 2.75rem; padding: 0.5rem 0.5rem;
    text-align: right; white-space: pre; overflow: hidden;
    background: var(--em-gray-100); color: var(--em-gray-400);
    font-family: monospace; font-size: 0.8125rem; line-height: 1.5; user-select: none;
}
.code-input__area {
    flex: 1; min-height: 160px; padding: 0.5rem 0.75rem;
    border: 0; outline: 0; resize: vertical; background: transparent;
    color: var(--em-gray-800); font-family: monospace; font-size: 0.8125rem;
    line-height: 1.5; white-space: pre; overflow: auto;
}
.code-input__menu { position: absolute; top: 0.25rem; right: 0.25rem; z-index: 2; }
.code-input__menu .btn { color: var(--em-gray-500); background: transparent; }
.code-input__menu .btn:hover { color: var(--em-gray-800); background: var(--em-gray-100); }

/* Category tabs */
.recon-tabs {
    overflow-x: auto; overflow-y: hidden; flex-wrap: nowrap;
    border-bottom: 1px solid var(--em-gray-200);
    scrollbar-width: thin;
}
.recon-tabs .nav-link {
    white-space: nowrap; color: var(--em-gray-600);
    border: none; border-bottom: 2px solid transparent;
    padding: 0.5rem 0.85rem; font-size: 0.85rem; font-weight: 500;
}
.recon-tabs .nav-link:hover { color: var(--em-primary); border-bottom-color: var(--em-gray-300); }
.recon-tabs .nav-link.active {
    color: var(--em-primary); background: transparent;
    border-bottom-color: var(--em-primary);
}
.recon-soc { border: none; margin-bottom: 0.75rem; }
CSS;
$this->registerCss($css);

$js = <<<'JS'
(function () {
    var area = document.getElementById('statementArea');
    var gutter = document.getElementById('statementGutter');
    var badge = document.getElementById('lineCountBadge');
    if (!area) { return; }

    function refresh() {
        var count = area.value === '' ? 0 : area.value.split('\n').length;
        var shown = Math.max(count, 1);
        var nums = '';
        for (var i = 1; i <= shown; i++) { nums += i + '\n'; }
        gutter.textContent = nums;
        gutter.scrollTop = area.scrollTop;
        badge.textContent = count + ' ' + (count === 1 ? 'line' : 'lines');
    }

    area.addEventListener('input', refresh);
    area.addEventListener('scroll', function () { gutter.scrollTop = area.scrollTop; });
    refresh();

    var clearBtn = document.getElementById('reconClear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () { area.value = ''; refresh(); area.focus(); });
    }
    var copyBtn = document.getElementById('reconCopy');
    if (copyBtn && navigator.clipboard) {
        copyBtn.addEventListener('click', function () { navigator.clipboard.writeText(area.value); });
    }

    // CSV dropzone
    var dz = document.getElementById('reconDropzone');
    var input = document.getElementById('reconFileInput');
    var placeholder = document.getElementById('reconPlaceholder');
    var picked = document.getElementById('reconPicked');
    var nameEl = document.getElementById('reconFileName');
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
        if (e.target.closest('#reconRemove')) { return; }
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
    var removeBtn = document.getElementById('reconRemove');
    if (removeBtn) {
        removeBtn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); resetFile(); });
    }
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);

// ------------------------------------------------------------------
// In-place update after adding a missing line as an expense.
// The "+" button opens the expense modal; on a successful save nem.js
// fires "nem:form:success". We remove the added statement line and
// adjust the counts/totals instead of reloading (this page is a POST
// result, so a reload would prompt Firefox to resubmit the statement).
// ------------------------------------------------------------------
$reconJs = 'var RECON_I18N = ' . json_encode([
    'allClear' => '<i class="bi bi-check-circle me-1"></i>'
        . Yii::t('app', 'Nothing missing - every statement line is accounted for.'),
], JSON_UNESCAPED_UNICODE) . ";\n";

$reconJs .= <<<'JS'
(function () {
    var pendingRow = null;

    // Formats a number the same way the PHP formatter does here
    // (comma thousands, two decimals) so subtotals stay consistent.
    function fmtDecimal(v) {
        return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function bumpStat(selector, delta) {
        var el = document.querySelector(selector);
        if (!el) { return; }
        var n = parseInt((el.textContent || '').replace(/[^0-9-]/g, ''), 10);
        if (isNaN(n)) { n = 0; }
        n += delta;
        if (n < 0) { n = 0; }
        el.textContent = n.toLocaleString('en-US');
    }

    function showAllClear() {
        var tabs = document.querySelector('.recon-tabs');
        var content = document.querySelector('.tab-content');
        var anchor = tabs || content;
        if (!anchor) { return; }
        var msg = document.createElement('div');
        msg.className = 'text-center py-4 text-success';
        msg.innerHTML = RECON_I18N.allClear;
        anchor.parentNode.insertBefore(msg, anchor);
        if (tabs) { tabs.parentNode.removeChild(tabs); }
        if (content) { content.parentNode.removeChild(content); }
    }

    function removeReconRow(row) {
        var pane = row.closest('.tab-pane');
        row.parentNode.removeChild(row);

        // One line moved from "not matched" to "matched".
        bumpStat('.recon-stat-matched', 1);
        bumpStat('.recon-stat-missing', -1);

        if (!pane) { return; }

        var rows = pane.querySelectorAll('tbody tr');
        var subtotal = 0;
        Array.prototype.forEach.call(rows, function (r) {
            subtotal += parseFloat(r.getAttribute('data-amount')) || 0;
        });

        var subEl = pane.querySelector('.recon-subtotal');
        if (subEl) { subEl.textContent = fmtDecimal(subtotal); }
        var cntEl = pane.querySelector('.recon-line-count');
        if (cntEl) { cntEl.textContent = rows.length; }

        var paneId = pane.getAttribute('id');
        var tabBtn = document.querySelector('[data-bs-target="#' + paneId + '"]');
        if (tabBtn) {
            var badge = tabBtn.querySelector('.badge');
            if (badge) { badge.textContent = rows.length; }
        }

        // Category emptied: drop its tab and pane, then reveal the next one.
        if (rows.length === 0) {
            var li = tabBtn ? tabBtn.closest('li') : null;
            var wasActive = tabBtn && tabBtn.classList.contains('active');
            if (li) { li.parentNode.removeChild(li); }
            pane.parentNode.removeChild(pane);

            var remaining = document.querySelectorAll('.recon-tabs .nav-link');
            if (!remaining.length) {
                showAllClear();
            } else if (wasActive && window.bootstrap && bootstrap.Tab) {
                new bootstrap.Tab(remaining[0]).show();
            }
        }
    }

    // Remember which statement line spawned the modal.
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.tab-pane tbody tr .btn-modal');
        if (addBtn) { pendingRow = addBtn.closest('tr'); }
    });

    $(document).on('nem:form:success', function (e, response, $form) {
        if (!$form || $form.data('source') !== 'reconcile' || !pendingRow) { return; }
        removeReconRow(pendingRow);
        pendingRow = null;
    });
})();
JS;
$this->registerJs($reconJs, \yii\web\View::POS_END);
