<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Import Wizard
 *
 * Upload a CSV/Excel file, preview the parsed rows with per-row validation,
 * then confirm to import. All requests are AJAX; nothing is written until the
 * user confirms.
 *
 * @var yii\web\View $this
 * @var app\models\ImportForm $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;
use app\models\Bank;
use app\models\ExpenseCategory;
use app\models\ImportForm;
use app\models\IncomeCategory;
use app\services\BankStatementParser;
use app\services\ImportService;

$this->title = Yii::t('app', 'Import Data');

$typeOptions = [
    ImportService::TYPE_EXPENSE => Yii::t('app', 'Expenses'),
    ImportService::TYPE_INCOME => Yii::t('app', 'Income'),
];

// Spell the accepted file types out in the option itself, so the choice is
// clear before the drop zone hint below it is read.
$sourceOptions = [
    ImportForm::SOURCE_SPREADSHEET => Yii::t('app', 'Spreadsheet (.csv, .xlsx, .xls)'),
    ImportForm::SOURCE_STATEMENT => Yii::t('app', 'Bank statement (.pdf)'),
];

$previewUrl = Url::to(['preview']);
$runUrl = Url::to(['run']);
$templateUrl = Url::to(['template']);
$expenseListUrl = Url::to(['/expense']);
$incomeListUrl = Url::to(['/income']);
$expenseCols = implode(', ', ImportService::columnsFor(ImportService::TYPE_EXPENSE));
$incomeCols = implode(', ', ImportService::columnsFor(ImportService::TYPE_INCOME));

$banks = Bank::getList();
$statementFormats = BankStatementParser::formatList();

// The fallback category must match the import type, so both lists are handed
// to the page and the dropdown is refilled when the type changes.
$categoriesByType = [
    ImportService::TYPE_EXPENSE => ExpenseCategory::getExpenseCategoryHierarchy(),
    ImportService::TYPE_INCOME => IncomeCategory::getIncomeCategory(),
];
$categories = $categoriesByType[ImportService::TYPE_EXPENSE];
?>

<div class="import-index">
    <!-- Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Bulk-import transactions from a CSV or Excel spreadsheet') ?>
            </p>
        </div>
        <a href="<?= $templateUrl ?>" id="template-link" class="btn btn-outline-secondary" data-pjax="0">
            <i class="bi bi-download me-1"></i><?= Yii::t('app', 'Download Template') ?>
        </a>
    </div>

    <div class="row g-4">
        <!-- Upload form -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-upload me-2 text-primary"></i><?= Yii::t('app', 'Upload File') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'import-form',
                        'options' => ['enctype' => 'multipart/form-data'],
                        // The wizard never submits normally: it posts through
                        // fetch() and renders whatever the JSON response says.
                        // Client validation would only duplicate that, and it
                        // cannot express the source-dependent rules anyway.
                        'enableClientValidation' => false,
                        // Also keep yii.activeForm off this form entirely.
                        // enableClientValidation alone does not do that, and
                        // its submit handler re-submits the form natively a
                        // moment after ours has already taken over, which
                        // reloads the page mid-request.
                        'enableClientScript' => false,
                    ]); ?>

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <?= $form->field($model, 'type')->dropDownList($typeOptions, ['id' => 'import-type'])
                                    ->label(Yii::t('app', 'Import Type')) ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $form->field($model, 'source')->dropDownList($sourceOptions, ['id' => 'import-source'])
                                    ->label(Yii::t('app', 'Source')) ?>
                            </div>
                        </div>

                        <!-- Drop zone -->
                        <div class="mb-3">
                            <label class="form-label"><?= Yii::t('app', 'File') ?></label>
                            <div id="drop-zone" class="border border-2 border-dashed rounded p-4 text-center" style="cursor: pointer;">
                                <i class="bi bi-file-earmark-spreadsheet text-primary" style="font-size: 2rem;"></i>
                                <p class="mb-1 mt-2" id="drop-text"><?= Yii::t('app', 'Drop file here or click to upload') ?></p>
                                <small class="text-muted" data-accept="spreadsheet"><?= Yii::t('app', 'Accepted: .csv, .xlsx, .xls (max 5 MB)') ?></small>
                                <small class="text-muted d-none" data-accept="statement"><?= Yii::t('app', 'Accepted: .pdf, .txt (max 5 MB)') ?></small>
                                <?= Html::activeFileInput($model, 'file', ['id' => 'import-file', 'class' => 'd-none', 'accept' => '.csv,.xlsx,.xls']) ?>
                            </div>
                            <div class="invalid-feedback d-block" id="file-error"></div>
                        </div>

                        <!-- Bank statement settings -->
                        <div id="statement-fields" class="d-none">
                            <?= $form->field($model, 'statementFormat')->dropDownList($statementFormats, [
                                'id' => 'import-statement-format',
                                'prompt' => Yii::t('app', 'Detect automatically'),
                            ])->label(Yii::t('app', 'Statement format'))
                                ->hint(Yii::t('app', 'Leave on auto unless you want the upload rejected when it is not this bank.'), ['class' => 'form-text small']) ?>

                            <?= $form->field($model, 'password')->passwordInput([
                                'id' => 'import-password',
                                'autocomplete' => 'off',
                            ])->label(Yii::t('app', 'PDF password'))
                                ->hint(Yii::t('app', 'Only needed for a protected statement. It is used to read the file and never stored.'), ['class' => 'form-text small']) ?>

                            <?= $form->field($model, 'bank_id')->dropDownList($banks, [
                                'id' => 'import-bank',
                                'prompt' => Yii::t('app', '- Select Bank -'),
                            ])->label(Yii::t('app', 'Bank'))
                                ->hint(Yii::t('app', 'Tagged onto every imported row.'), ['class' => 'form-text small']) ?>

                            <?= $form->field($model, 'fallbackCategoryId')->dropDownList($categories, [
                                'id' => 'import-fallback-category',
                                'prompt' => Yii::t('app', '- Select Category -'),
                            ])->label(Yii::t('app', 'Fallback category'))
                                ->hint(Yii::t('app', 'Used for rows the description rules could not classify.'), ['class' => 'form-text small']) ?>
                        </div>

                        <!-- Options -->
                        <div class="form-check form-switch mb-2">
                            <?= Html::activeCheckbox($model, 'autoCreateCategories', ['class' => 'form-check-input', 'label' => false, 'id' => 'opt-autocreate']) ?>
                            <label class="form-check-label" for="opt-autocreate"><?= Yii::t('app', 'Create missing categories automatically') ?></label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <?= Html::activeCheckbox($model, 'skipDuplicates', ['class' => 'form-check-input', 'label' => false, 'id' => 'opt-skipdupes']) ?>
                            <label class="form-check-label" for="opt-skipdupes"><?= Yii::t('app', 'Skip duplicate rows') ?></label>
                        </div>
                        <div class="form-check form-switch mb-3 d-none" data-statement-option>
                            <?= Html::activeCheckbox($model, 'includeTransfers', ['class' => 'form-check-input', 'label' => false, 'id' => 'opt-transfers']) ?>
                            <label class="form-check-label" for="opt-transfers"><?= Yii::t('app', 'Include cash withdrawals and account transfers') ?></label>
                            <div class="form-text small"><?= Yii::t('app', 'Off by default: these move money rather than spend it, so importing them double-counts.') ?></div>
                        </div>

                        <?= Html::submitButton('<i class="bi bi-eye me-1"></i>' . Yii::t('app', 'Preview'), [
                            'class' => 'btn btn-primary w-100',
                            'id' => 'preview-btn',
                        ]) ?>

                    <?php ActiveForm::end(); ?>

                    <!-- Expected columns hint -->
                    <div class="alert alert-light border mt-3 mb-0 small" data-hint="spreadsheet">
                        <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i><?= Yii::t('app', 'Expected columns') ?></div>
                        <div data-cols="expense"><?= Html::encode($expenseCols) ?></div>
                        <div data-cols="income" class="d-none"><?= Html::encode($incomeCols) ?></div>
                    </div>

                    <!-- Supported statement formats -->
                    <div class="alert alert-light border mt-3 mb-0 small d-none" data-hint="statement">
                        <div class="fw-semibold mb-1"><i class="bi bi-bank me-1"></i><?= Yii::t('app', 'Supported statements') ?></div>
                        <ul class="mb-2 ps-3">
                            <?php foreach ($statementFormats as $label): ?>
                                <li><?= Html::encode($label) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-muted">
                            <?= Yii::t('app', 'The format is detected automatically. Imported rows are saved as drafts for review.') ?>
                            <?= Yii::t('app', 'Drafts are visible in the expense list and are included in totals until you delete them.') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview / result -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2 text-primary"></i><?= Yii::t('app', 'Preview') ?>
                    </h5>
                    <button type="button" class="btn btn-success d-none" id="import-btn">
                        <i class="bi bi-check2-circle me-1"></i><span id="import-btn-label"><?= Yii::t('app', 'Import') ?></span>
                    </button>
                </div>
                <div class="card-body">
                    <div id="preview-placeholder" class="text-center text-muted py-5">
                        <i class="bi bi-arrow-left-circle" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0"><?= Yii::t('app', 'Upload a file and click Preview to see the rows here.') ?></p>
                    </div>
                    <div id="preview-result"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$categoriesJson = json_encode($categoriesByType, JSON_UNESCAPED_UNICODE);
// json_encode, not a bare quoted string: some locales translate this with an
// apostrophe, which would end the JS string literal early.
$selectCategoryTxt = json_encode(Yii::t('app', '- Select Category -'), JSON_UNESCAPED_UNICODE);
$importLabelTpl = Yii::t('app', 'Import {count} rows');
$importingTxt = Yii::t('app', 'Importing...');
$previewingTxt = Yii::t('app', 'Parsing...');
$js = <<<JS
(function() {
    'use strict';

    function boot() {

    var form = document.getElementById('import-form');
    var fileInput = document.getElementById('import-file');
    var dropZone = document.getElementById('drop-zone');
    var dropText = document.getElementById('drop-text');
    var typeSelect = document.getElementById('import-type');
    var sourceSelect = document.getElementById('import-source');
    var statementFields = document.getElementById('statement-fields');
    var previewBtn = document.getElementById('preview-btn');
    var importBtn = document.getElementById('import-btn');
    var importBtnLabel = document.getElementById('import-btn-label');
    var placeholder = document.getElementById('preview-placeholder');
    var resultBox = document.getElementById('preview-result');
    var templateLink = document.getElementById('template-link');
    var fileError = document.getElementById('file-error');
    var currentToken = null;

    // Fail loudly. A missing element used to throw partway through setup,
    // leaving the form with no submit handler at all, so clicking Preview
    // just posted the page back and looked like nothing had happened.
    if (!form || !fileInput || !sourceSelect || !statementFields) {
        console.error('Import wizard: expected form elements are missing; setup aborted.');
        return;
    }

    // Paint (or clear) the server's field errors next to the input they
    // belong to. Always called with the new response, so a message from an
    // earlier attempt cannot linger over a field that is now valid.
    function setFieldErrors(errors) {
        ['password', 'statementFormat', 'bank_id', 'fallbackCategoryId'].forEach(function(field) {
            var input = form.querySelector('[name="ImportForm[' + field + ']"]');
            if (!input) return;
            var msg = errors && errors[field] ? errors[field][0] : '';
            input.classList.toggle('is-invalid', msg !== '');
            var box = input.parentNode.querySelector('.invalid-feedback');
            if (box) { box.textContent = msg; }
        });
    }

    // Never let a failure be invisible: toast when the toast library is
    // there, and always write the message into the preview panel.
    function report(kind, message) {
        if (window.NEM && NEM.Toast && NEM.Toast[kind]) {
            NEM.Toast[kind](message);
        }
        if (kind === 'error') {
            placeholder.classList.add('d-none');
            resultBox.innerHTML = '<div class="alert alert-danger mb-0">' +
                '<i class="bi bi-exclamation-triangle me-1"></i>' +
                String(message).replace(/[<>&]/g, '') + '</div>';
        }
    }

    function csrf() {
        return {
            param: document.querySelector('meta[name="csrf-param"]').getAttribute('content'),
            token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
    }

    function isStatement() {
        return sourceSelect.value === 'statement';
    }

    var categoriesByType = {$categoriesJson};

    // Keep template link, column hint and fallback categories in sync with the
    // selected type: expense and income have separate category lists.
    function syncType() {
        var t = typeSelect.value;
        templateLink.setAttribute('href', '{$templateUrl}?type=' + encodeURIComponent(t));
        document.querySelectorAll('[data-cols]').forEach(function(el) {
            el.classList.toggle('d-none', el.getAttribute('data-cols') !== t);
        });

        var select = document.getElementById('import-fallback-category');
        var previous = select.value;
        var list = categoriesByType[t] || {};
        select.innerHTML = '';
        select.appendChild(new Option({$selectCategoryTxt}, ''));
        Object.keys(list).forEach(function(id) {
            select.appendChild(new Option(list[id], id));
        });
        if (previous && list[previous]) { select.value = previous; }
    }
    typeSelect.addEventListener('change', syncType);
    syncType();

    // A bank statement needs a bank, a fallback category and possibly a
    // password, and accepts a different set of file types.
    function syncSource() {
        var statement = isStatement();
        var key = statement ? 'statement' : 'spreadsheet';

        statementFields.classList.toggle('d-none', !statement);
        templateLink.classList.toggle('d-none', statement);
        document.querySelectorAll('[data-statement-option]').forEach(function(el) {
            el.classList.toggle('d-none', !statement);
        });
        document.querySelectorAll('[data-hint]').forEach(function(el) {
            el.classList.toggle('d-none', el.getAttribute('data-hint') !== key);
        });
        document.querySelectorAll('[data-accept]').forEach(function(el) {
            el.classList.toggle('d-none', el.getAttribute('data-accept') !== key);
        });

        fileInput.setAttribute('accept', statement ? '.pdf,.txt' : '.csv,.xlsx,.xls');
    }
    sourceSelect.addEventListener('change', syncSource);
    syncSource();

    // Drop zone interactions
    dropZone.addEventListener('click', function() { fileInput.click(); });
    fileInput.addEventListener('change', function() {
        if (fileInput.files.length) { dropText.textContent = fileInput.files[0].name; }
    });
    ['dragover', 'dragenter'].forEach(function(ev) {
        dropZone.addEventListener(ev, function(e) { e.preventDefault(); dropZone.classList.add('border-primary', 'bg-light'); });
    });
    ['dragleave', 'drop'].forEach(function(ev) {
        dropZone.addEventListener(ev, function(e) { e.preventDefault(); dropZone.classList.remove('border-primary', 'bg-light'); });
    });
    dropZone.addEventListener('drop', function(e) {
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            dropText.textContent = e.dataTransfer.files[0].name;
        }
    });

    // Preview (upload + validate)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fileError.textContent = '';
        if (!fileInput.files.length) {
            fileError.textContent = 'Please choose a file first.';
            return;
        }
        var fd = new FormData(form);
        var original = previewBtn.innerHTML;
        previewBtn.disabled = true;
        previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{$previewingTxt}';
        importBtn.classList.add('d-none');

        fetch('{$previewUrl}', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                previewBtn.disabled = false;
                previewBtn.innerHTML = original;
                setFieldErrors(resp.errors);
                if (resp.status === 'success') {
                    placeholder.classList.add('d-none');
                    resultBox.innerHTML = resp.html;
                    currentToken = resp.token;
                    if (resp.summary.importable > 0) {
                        importBtnLabel.textContent = '{$importLabelTpl}'.replace('{count}', resp.summary.importable);
                        importBtn.classList.remove('d-none');
                    }
                    report('success', resp.message);
                } else {
                    resultBox.innerHTML = '';
                    placeholder.classList.remove('d-none');
                    if (resp.errors && resp.errors.file) { fileError.textContent = resp.errors.file[0]; }
                    report('error', resp.message || 'Preview failed.');
                }
            })
            .catch(function(err) {
                previewBtn.disabled = false;
                previewBtn.innerHTML = original;
                console.error('Import preview request failed:', err);
                report('error', 'Preview request failed: ' + (err && err.message ? err.message : err));
            });
    });

    // Run import
    importBtn.addEventListener('click', function() {
        if (!currentToken) return;
        var c = csrf();
        var data = new URLSearchParams();
        data.append('token', currentToken);
        data.append('type', typeSelect.value);
        data.append('source', sourceSelect.value);
        data.append('autoCreateCategories', document.getElementById('opt-autocreate').checked ? 1 : 0);
        data.append('skipDuplicates', document.getElementById('opt-skipdupes').checked ? 1 : 0);
        // The staged statement is already text, so no password is resent here.
        if (isStatement()) {
            data.append('includeTransfers', document.getElementById('opt-transfers').checked ? 1 : 0);
            data.append('statementFormat', document.getElementById('import-statement-format').value);
            data.append('bankId', document.getElementById('import-bank').value);
            data.append('fallbackCategoryId', document.getElementById('import-fallback-category').value);
        }
        data.append(c.param, c.token);

        var original = importBtn.innerHTML;
        importBtn.disabled = true;
        importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{$importingTxt}';

        fetch('{$runUrl}', {
            method: 'POST',
            body: data.toString(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }
        })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                importBtn.disabled = false;
                importBtn.innerHTML = original;
                var listUrl = typeSelect.value === 'income' ? '{$incomeListUrl}' : '{$expenseListUrl}';
                var cls = resp.status === 'success' ? 'alert-success' : 'alert-warning';
                resultBox.innerHTML = '<div class="alert ' + cls + '">' +
                    '<i class="bi bi-check2-circle me-1"></i>' + resp.message +
                    ' <a href="' + listUrl + '" class="alert-link ms-2">View &rarr;</a></div>' +
                    (resp.errors && resp.errors.length ? '<ul class="small text-danger mb-0">' +
                        resp.errors.map(function(x){ return '<li>' + x + '</li>'; }).join('') + '</ul>' : '');
                importBtn.classList.add('d-none');
                currentToken = null;
                report(resp.status === 'success' ? 'success' : 'warning', resp.message);
            })
            .catch(function(err) {
                importBtn.disabled = false;
                importBtn.innerHTML = original;
                console.error('Import run request failed:', err);
                report('error', 'Import request failed: ' + (err && err.message ? err.message : err));
            });
    });

    }

    // Stand on our own: no jQuery, and nothing else's ready handler can stop
    // this from binding. At POS_END the form markup above is already parsed.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
