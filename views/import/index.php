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
use app\services\ImportService;

$this->title = Yii::t('app', 'Import Data');

$typeOptions = [
    ImportService::TYPE_EXPENSE => Yii::t('app', 'Expenses'),
    ImportService::TYPE_INCOME => Yii::t('app', 'Income'),
];

$previewUrl = Url::to(['preview']);
$runUrl = Url::to(['run']);
$templateUrl = Url::to(['template']);
$expenseListUrl = Url::to(['/expense']);
$incomeListUrl = Url::to(['/income']);
$expenseCols = implode(', ', ImportService::columnsFor(ImportService::TYPE_EXPENSE));
$incomeCols = implode(', ', ImportService::columnsFor(ImportService::TYPE_INCOME));
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
                    ]); ?>

                        <?= $form->field($model, 'type')->dropDownList($typeOptions, ['id' => 'import-type'])
                            ->label(Yii::t('app', 'Import Type')) ?>

                        <!-- Drop zone -->
                        <div class="mb-3">
                            <label class="form-label"><?= Yii::t('app', 'File') ?></label>
                            <div id="drop-zone" class="border border-2 border-dashed rounded p-4 text-center" style="cursor: pointer;">
                                <i class="bi bi-file-earmark-spreadsheet text-primary" style="font-size: 2rem;"></i>
                                <p class="mb-1 mt-2" id="drop-text"><?= Yii::t('app', 'Drop file here or click to upload') ?></p>
                                <small class="text-muted"><?= Yii::t('app', 'Accepted: .csv, .xlsx, .xls (max 5 MB)') ?></small>
                                <?= Html::activeFileInput($model, 'file', ['id' => 'import-file', 'class' => 'd-none', 'accept' => '.csv,.xlsx,.xls']) ?>
                            </div>
                            <div class="invalid-feedback d-block" id="file-error"></div>
                        </div>

                        <!-- Options -->
                        <div class="form-check form-switch mb-2">
                            <?= Html::activeCheckbox($model, 'autoCreateCategories', ['class' => 'form-check-input', 'label' => false, 'id' => 'opt-autocreate']) ?>
                            <label class="form-check-label" for="opt-autocreate"><?= Yii::t('app', 'Create missing categories automatically') ?></label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <?= Html::activeCheckbox($model, 'skipDuplicates', ['class' => 'form-check-input', 'label' => false, 'id' => 'opt-skipdupes']) ?>
                            <label class="form-check-label" for="opt-skipdupes"><?= Yii::t('app', 'Skip duplicate rows') ?></label>
                        </div>

                        <?= Html::submitButton('<i class="bi bi-eye me-1"></i>' . Yii::t('app', 'Preview'), [
                            'class' => 'btn btn-primary w-100',
                            'id' => 'preview-btn',
                        ]) ?>

                    <?php ActiveForm::end(); ?>

                    <!-- Expected columns hint -->
                    <div class="alert alert-light border mt-3 mb-0 small">
                        <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i><?= Yii::t('app', 'Expected columns') ?></div>
                        <div data-cols="expense"><?= Html::encode($expenseCols) ?></div>
                        <div data-cols="income" class="d-none"><?= Html::encode($incomeCols) ?></div>
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
$importLabelTpl = Yii::t('app', 'Import {count} rows');
$importingTxt = Yii::t('app', 'Importing...');
$previewingTxt = Yii::t('app', 'Parsing...');
$js = <<<JS
(function() {
    'use strict';

    var form = document.getElementById('import-form');
    var fileInput = document.getElementById('import-file');
    var dropZone = document.getElementById('drop-zone');
    var dropText = document.getElementById('drop-text');
    var typeSelect = document.getElementById('import-type');
    var previewBtn = document.getElementById('preview-btn');
    var importBtn = document.getElementById('import-btn');
    var importBtnLabel = document.getElementById('import-btn-label');
    var placeholder = document.getElementById('preview-placeholder');
    var resultBox = document.getElementById('preview-result');
    var templateLink = document.getElementById('template-link');
    var fileError = document.getElementById('file-error');
    var currentToken = null;

    function csrf() {
        return {
            param: document.querySelector('meta[name="csrf-param"]').getAttribute('content'),
            token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
    }

    // Keep template link + column hint in sync with the selected type
    function syncType() {
        var t = typeSelect.value;
        templateLink.setAttribute('href', '{$templateUrl}?type=' + encodeURIComponent(t));
        document.querySelectorAll('[data-cols]').forEach(function(el) {
            el.classList.toggle('d-none', el.getAttribute('data-cols') !== t);
        });
    }
    typeSelect.addEventListener('change', syncType);
    syncType();

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
                if (resp.status === 'success') {
                    placeholder.classList.add('d-none');
                    resultBox.innerHTML = resp.html;
                    currentToken = resp.token;
                    if (resp.summary.importable > 0) {
                        importBtnLabel.textContent = '{$importLabelTpl}'.replace('{count}', resp.summary.importable);
                        importBtn.classList.remove('d-none');
                    }
                    if (window.NEM) NEM.Toast.success(resp.message);
                } else {
                    resultBox.innerHTML = '';
                    placeholder.classList.remove('d-none');
                    if (resp.errors && resp.errors.file) { fileError.textContent = resp.errors.file[0]; }
                    if (window.NEM) NEM.Toast.error(resp.message || 'Preview failed.');
                }
            })
            .catch(function() {
                previewBtn.disabled = false;
                previewBtn.innerHTML = original;
                if (window.NEM) NEM.Toast.error('Request failed. Please try again.');
            });
    });

    // Run import
    importBtn.addEventListener('click', function() {
        if (!currentToken) return;
        var c = csrf();
        var data = new URLSearchParams();
        data.append('token', currentToken);
        data.append('type', typeSelect.value);
        data.append('autoCreateCategories', document.getElementById('opt-autocreate').checked ? 1 : 0);
        data.append('skipDuplicates', document.getElementById('opt-skipdupes').checked ? 1 : 0);
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
                if (window.NEM) (resp.status === 'success' ? NEM.Toast.success : NEM.Toast.warning)(resp.message);
            })
            .catch(function() {
                importBtn.disabled = false;
                importBtn.innerHTML = original;
                if (window.NEM) NEM.Toast.error('Import failed. Please try again.');
            });
    });
})();
JS;
$this->registerJs($js);
?>
