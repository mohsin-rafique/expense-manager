<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Form Partial for Expenses
 *
 * Professional form for creating and updating expense records.
 * Supports file uploads, category selection, and payment methods.
 *
 * @var yii\web\View $this
 * @var app\models\Expense $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use app\models\ExpenseCategory;
use app\models\Expense;

$isNewRecord = $model->isNewRecord;
$categories = ExpenseCategory::getExpenseCategoryHierarchy();
$fbrCategories = ExpenseCategory::getFbrCategories();

$showFbr = (Yii::$app->user->identity?->profile?->country_code) === 'PK';
?>

<?php $form = ActiveForm::begin([
    'id' => 'expense-form',
    'action' => $isNewRecord ? Url::to(['create']) : Url::to(['update', 'id' => $model->id]),
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => 'needs-validation data-form expense-form',
        'data-container' => 'expenses-pjax',
    ],
    'enableClientValidation' => true,
    'enableAjaxValidation' => false,
]); ?>

<?= Html::activeHiddenInput($model, 'user_id', ['value' => Yii::$app->user->id]); ?>

<div class="row g-4">
    <!-- Main Fields Column -->
    <div class="col-lg-7">
        <!-- Category Selection -->
        <div class="mb-3">
            <?= $form->field($model, 'expense_category_id', [
                'options' => ['class' => 'mb-0'],
                'template' => '{label}{input}{hint}{error}',
            ])->dropDownList($categories, [
                'class' => 'form-select js-choices',
                'prompt' => Yii::t('app', '- Select Category -'),
                'id' => 'expense-category-select',
                'data-search-placeholder' => Yii::t('app', 'Search categories...'),
            ])->label('<i class="bi bi-folder me-1 text-danger"></i>' . Yii::t('app', 'Category') . ' <span class="text-danger">*</span>') ?>
        </div>

        <?php if ($showFbr) { ?>
        <div class="mb-3">
            <?= $form->field($model, 'fbr_category', [
                'options' => ['class' => 'mb-0'],
                'template' => '{label}{input}{hint}{error}',
            ])->dropDownList(
                $fbrCategories,
                [
                    'class' => 'form-select js-choices',
                    'prompt' => 'Select FBR Category...'
                ]
            ); ?>
        </div>
        <?php } ?>

        <!-- Date -->
        <div class="mb-3">
            <?= $form->field($model, 'expense_date', [
                'options' => ['class' => 'mb-0'],
            ])->input('date', [
                'class' => 'form-control',
                'value' => $model->expense_date ?: date('Y-m-d'),
            ])->label('<i class="bi bi-calendar3 me-1 text-danger"></i>' . Yii::t('app', 'Date') . ' <span class="text-danger">*</span>') ?>
        </div>

        <!-- Payment Method -->
        <div class="mb-3">
            <?= $form->field($model, 'payment_method', [
                'options' => ['class' => 'mb-0'],
                'template' => '{label}{input}{hint}{error}',
            ])->dropDownList(Expense::getPaymentMethods(), [
                'class' => 'form-select js-choices',
                'prompt' => Yii::t('app', '- Select Payment Method -'),
            ])->label('<i class="bi bi-credit-card me-1 text-danger"></i>' . Yii::t('app', 'Payment Method')) ?>
        </div>

        <!-- Amount -->
        <div class="mb-3">
            <?= $form->field($model, 'amount', [
                'options' => ['class' => 'mb-0'],
                'template' => '{label}<div class="input-group">' .
                    '<span class="input-group-text bg-danger-subtle text-danger border-end-0">' .
                    Yii::$app->currency->getSymbol() . '</span>{input}</div>{hint}{error}',
            ])->textInput([
                'class' => 'form-control border-start-0',
                'placeholder' => '0.00',
                'inputmode' => 'decimal',
                'autocomplete' => 'off',
                'id' => 'expense-amount',
            ])->label('<i class="bi bi-cash-stack me-1 text-danger"></i>' . Yii::t('app', 'Amount') . ' <span class="text-danger">*</span>') ?>
        </div>

        <!-- Reference -->
        <div class="mb-3">
            <?= $form->field($model, 'reference', [
                'options' => ['class' => 'mb-0'],
            ])->textInput([
                'class' => 'form-control',
                'maxlength' => true,
                'placeholder' => Yii::t('app', 'Invoice #, Receipt #, etc.'),
            ])->label('<i class="bi bi-hash me-1 text-muted"></i>' . Yii::t('app', 'Reference')) ?>
        </div>

        <!-- Description -->
        <div class="mb-3">
            <?= $form->field($model, 'description', [
                'options' => ['class' => 'mb-0'],
            ])->textarea([
                'class' => 'form-control',
                'rows' => 3,
                'placeholder' => Yii::t('app', 'Additional notes or details about this expense...'),
            ])->label('<i class="bi bi-card-text me-1 text-muted"></i>' . Yii::t('app', 'Description')) ?>
        </div>
    </div>

    <!-- Sidebar Column -->
    <div class="col-lg-5">
        <!-- File Upload Card -->
        <div class="card border-0 bg-light rounded-3">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-paperclip me-1 text-muted"></i>
                    <?= Yii::t('app', 'Attachment') ?>
                </h6>

                <div class="alert border-0 bg-danger-subtle text-danger py-2 px-3 small mb-3 d-flex align-items-center">
                    <i class="bi bi-stars me-2"></i>
                    <span><?= Yii::t('app', 'Upload a receipt photo and we\'ll read it in your browser and fill in the form for you.') ?></span>
                </div>

                <!-- Current Attachment (for update) -->
                <?php if (!$isNewRecord && $model->hasAttachment()): ?>
                    <div class="current-attachment mb-3 p-3 bg-white rounded-3 border">
                        <div class="d-flex align-items-center">
                            <div class="file-icon me-3">
                                <i class="bi <?= $model->getFileIcon() ?>" style="font-size: 2rem;"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-medium text-truncate" title="<?= Html::encode($model->filename) ?>">
                                    <?= Html::encode($model->filename) ?>
                                </div>
                                <small class="text-muted"><?= $model->getFileSizeFormatted() ?></small>
                            </div>
                            <div class="ms-2">
                                <a href="<?= $model->getFileUrl() ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    target="_blank"
                                    title="<?= Yii::t('app', 'Download') ?>">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= Yii::t('app', 'Upload a new file to replace the current one.') ?>
                    </p>
                <?php endif; ?>

                <!-- File Input -->
                <div class="upload-area" id="upload-area">
                    <div class="upload-placeholder text-center p-4" id="upload-placeholder">
                        <i class="bi bi-cloud-arrow-up text-danger" style="font-size: 2.5rem;"></i>
                        <p class="mb-1 mt-2"><?= Yii::t('app', 'Drop file here or click to upload') ?></p>
                        <small class="text-muted">PNG, JPG, JPEG, PDF (Max 12MB)</small>
                    </div>
                    <div class="upload-preview d-none" id="upload-preview">
                        <div class="d-flex align-items-center p-3">
                            <i class="bi bi-file-earmark me-3" style="font-size: 2rem;" id="preview-icon"></i>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-medium text-truncate" id="preview-name"></div>
                                <small class="text-muted" id="preview-size"></small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="remove-file">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <?= $form->field($model, 'myFile', [
                        'options' => ['class' => 'd-none'],
                        'template' => '{input}{error}',
                    ])->fileInput([
                                             'id' => 'expense-file-input',
                                             'accept' => '.png,.jpg,.jpeg,.pdf',
                    ]) ?>
                </div>

                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="auto-read-receipt">
                    <label class="form-check-label small" for="auto-read-receipt">
                        <i class="bi bi-magic me-1 text-danger"></i>
                        <?= Yii::t('app', 'Read this invoice and fill in the form') ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis ms-1"><?= Yii::t('app', 'Beta') ?></span>
                    </label>
                    <div class="form-text small">
                        <?= Yii::t('app', 'Automatic invoice reading is in testing mode at the moment - results may be inaccurate.') ?>
                    </div>
                </div>

                <div id="receipt-scan-status" class="d-none mt-3 p-2 rounded-3 small"></div>
            </div>
        </div>

        <!-- Quick Summary Card (for new records) -->
        <?php if ($isNewRecord): ?>
            <div class="card border-0 bg-danger-subtle rounded-3 mt-3">
                <div class="card-body">
                    <h6 class="card-title text-danger mb-3">
                        <i class="bi bi-lightbulb me-1"></i>
                        <?= Yii::t('app', 'Quick Tips') ?>
                    </h6>
                    <ul class="list-unstyled mb-0 small text-danger">
                        <li class="mb-2">
                            <i class="bi bi-check2 me-1"></i>
                            <?= Yii::t('app', 'Use reference for invoice or receipt numbers') ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check2 me-1"></i>
                            <?= Yii::t('app', 'Attach receipts for better record keeping') ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check2 me-1"></i>
                            <?= Yii::t('app', 'Select payment method for tracking') ?>
                        </li>
                        <li>
                            <i class="bi bi-check2 me-1"></i>
                            <?= Yii::t('app', 'Add descriptions for clarity') ?>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form Actions -->
<div class="d-flex gap-2 justify-content-end border-top pt-4 mt-4">
    <?= Html::button(
        Yii::t('app', 'Cancel'),
        [
            'class' => 'btn btn-light',
            'data-bs-dismiss' => 'modal',
        ]
    ) ?>
    <?= Html::submitButton(
        $isNewRecord
            ? '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Expense')
            : '<i class="bi bi-check-lg me-1"></i>' . Yii::t('app', 'Save Changes'),
        [
            'class' => $isNewRecord ? 'btn btn-danger' : 'btn btn-primary',
            'id' => 'submit-expense-btn',
        ]
    ) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
// Category list for the receipt matcher, sourced from PHP so it stays reliable
// regardless of how the searchable-select widget manipulates the DOM options.
$categoryOptions = [];
foreach ($categories as $catId => $catLabel) {
    $name = preg_replace('/^[\s\-]+/u', '', (string) $catLabel);
    if ($name !== '') {
        $categoryOptions[] = ['id' => (string) $catId, 'name' => $name];
    }
}

$scanConfig = json_encode([
    // Tesseract.js (browser-side OCR) - loaded on demand from a CDN.
    'lib' => 'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js',
    'lang' => 'eng',
    'loadingLib' => Yii::t('app', 'Loading receipt reader...'),
    'reading' => Yii::t('app', 'Reading your receipt...'),
    'noData' => Yii::t('app', 'Couldn\'t read details from this image. Please enter them manually.'),
    'failed' => Yii::t('app', 'Could not read the receipt. Please enter the details manually.'),
    'pdfNote' => Yii::t('app', 'PDF attached. Auto-reading works on photos/images (PNG, JPG) only.'),
    'filledPrefix' => Yii::t('app', 'Filled in:'),
    'suggestedCategory' => Yii::t('app', 'Suggested category:'),
    'categories' => $categoryOptions,
], JSON_UNESCAPED_UNICODE);

$css = <<<'CSS'
.receipt-filled {
    animation: receiptFlash 1.8s ease-out;
}
@keyframes receiptFlash {
    0% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}
.choices.receipt-filled .choices__inner {
    animation: receiptFlash 1.8s ease-out;
}
CSS;
$this->registerCss($css);

$js = "var RECEIPT_SCAN = {$scanConfig};\n";

$js .= <<<'JS'
$(function () {
    // Initialize file upload for expense form
    if (typeof NEM !== 'undefined' && NEM.FileUpload) {
        NEM.FileUpload.init({
            uploadAreaSelector: '#upload-area',
            fileInputSelector: '#expense-file-input',
            placeholderSelector: '#upload-placeholder',
            previewSelector: '#upload-preview',
            previewIconSelector: '#preview-icon',
            previewNameSelector: '#preview-name',
            previewSizeSelector: '#preview-size',
            removeBtnSelector: '#remove-file'
        });
    }

    var $status = $('#receipt-scan-status');
    var spinner = '<span class="spinner-border spinner-border-sm me-2"></span>';
    var libPromise = null;

    function setStatus(state, html) {
        if (!$status.length) { return; }
        $status.removeClass('d-none bg-light text-muted bg-danger-subtle text-danger bg-success-subtle text-success');
        if (state === 'loading') {
            $status.addClass('bg-light text-muted');
        } else if (state === 'error') {
            $status.addClass('bg-danger-subtle text-danger');
        } else if (state === 'success') {
            $status.addClass('bg-success-subtle text-success');
        } else if (state === 'info') {
            $status.addClass('bg-light text-muted');
        }
        $status.html(html);
    }

    function hideStatus() {
        if ($status.length) { $status.addClass('d-none').html(''); }
    }

    // Lazily load Tesseract.js the first time it's needed.
    function loadTesseract() {
        if (window.Tesseract) { return Promise.resolve(); }
        if (libPromise) { return libPromise; }
        libPromise = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = RECEIPT_SCAN.lib;
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () { libPromise = null; reject(new Error('Failed to load OCR library')); };
            document.head.appendChild(s);
        });
        return libPromise;
    }

    function fillField(selector, value) {
        var $el = $(selector);
        if (!$el.length) { return false; }
        var el = $el[0];

        // For searchable selects, go through the helper so the Choices UI updates.
        if (el.tagName === 'SELECT' && window.NEM && NEM.Select) {
            NEM.Select.setValue(el, value);
        } else {
            $el.val(value);
        }
        $el.trigger('change');

        // Flash the visible control (the Choices wrapper when the select is enhanced).
        var $flash = $el;
        try {
            if (el.choicesInstance && el.choicesInstance.containerOuter) {
                $flash = $(el.choicesInstance.containerOuter.element);
            }
        } catch (e) { /* fall back to the original element */ }
        $flash.addClass('receipt-filled');
        setTimeout(function () { $flash.removeClass('receipt-filled'); }, 1800);
        return true;
    }

    // ----- Text parsing helpers (all client-side, no network) -----

    function parseMoneyToken(tok) {
        tok = (tok || '').replace(/[^0-9.,]/g, '');
        if (!tok) { return null; }
        var dec = Math.max(tok.lastIndexOf(','), tok.lastIndexOf('.'));
        if (dec === -1) { return parseFloat(tok) || null; }
        var after = tok.slice(dec + 1);
        if (after.length === 1 || after.length === 2) {
            var intPart = tok.slice(0, dec).replace(/[.,]/g, '');
            return parseFloat(intPart + '.' + after) || null;
        }
        return parseFloat(tok.replace(/[.,]/g, '')) || null;
    }

    var MONEY_RE = /\d[\d.,]*\d|\d/g;

    function moneyMaxOnLine(line) {
        var matches = line.match(MONEY_RE) || [];
        var best = null;
        matches.forEach(function (m) {
            var v = parseMoneyToken(m);
            if (v !== null && (best === null || v > best)) { best = v; }
        });
        return best;
    }

    function parseAmount(text) {
        var lines = text.split(/\n+/);
        var candidates = [];
        lines.forEach(function (line) {
            if (/sub\s*-?\s*total/i.test(line)) { return; }
            if (/(grand\s*total|total\s*due|amount\s*due|balance\s*due|\btotal\b|amount\s*paid|\bpaid\b)/i.test(line)) {
                var v = moneyMaxOnLine(line);
                if (v !== null) { candidates.push(v); }
            }
        });
        var pick = null;
        if (candidates.length) {
            pick = Math.max.apply(null, candidates);
        } else {
            // Fallback: largest money-looking value anywhere.
            var all = text.match(MONEY_RE) || [];
            all.forEach(function (m) {
                var v = parseMoneyToken(m);
                if (v !== null && (pick === null || v > pick)) { pick = v; }
            });
        }
        return (pick !== null && pick > 0) ? pick.toFixed(2) : '';
    }

    function iso(y, mo, d) {
        return y + '-' + String(mo).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    // Look for a date inside a single string. `loose` also accepts plain
    // spaces as separators (used only for lines we know hold a date, e.g.
    // a "Date:" line, to survive OCR noise on crumpled receipts).
    function findDateIn(str, loose) {
        var m;
        m = str.match(/(20\d{2})\s*[-\/.]\s*(\d{1,2})\s*[-\/.]\s*(\d{1,2})/);
        if (m) { return iso(m[1], +m[2], +m[3]); }

        m = str.match(/(\d{1,2})\s*[-\/.]\s*(\d{1,2})\s*[-\/.]\s*(20\d{2}|\d{2})/);
        if (!m && loose) {
            m = str.match(/(\d{1,2})\s+(\d{1,2})\s+(20\d{2}|\d{2})/);
        }
        if (m) {
            var a = +m[1], b = +m[2];
            var year = m[3].length === 2 ? '20' + m[3] : m[3];
            var day, mon;
            if (a > 12) { day = a; mon = b; }
            else if (b > 12) { day = b; mon = a; }
            else {
                // Ambiguous (both <= 12). Receipts are for past purchases, so
                // prefer the reading that isn't in the future; else day-month.
                var today = new Date(); today.setHours(0, 0, 0, 0);
                var dm = new Date(+year, b - 1, a); // day-month
                var md = new Date(+year, a - 1, b); // month-day
                if (dm > today && md <= today) { day = b; mon = a; }
                else { day = a; mon = b; }
            }
            if (mon >= 1 && mon <= 12 && day >= 1 && day <= 31) { return iso(year, mon, day); }
        }

        var months = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };
        m = str.match(/(\d{1,2})\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s*(20\d{2})/i);
        if (m) { return iso(m[3], months[m[2].toLowerCase()], +m[1]); }
        m = str.match(/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s*(\d{1,2}),?\s*(20\d{2})/i);
        if (m) { return iso(m[3], months[m[1].toLowerCase()], +m[2]); }
        return '';
    }

    function parseDate(text) {
        // Prefer a line that explicitly mentions the date - avoids picking up
        // phone numbers or amounts, and lets us parse loosely on that line.
        var lines = text.split(/\n+/);
        for (var i = 0; i < lines.length; i++) {
            if (/date/i.test(lines[i])) {
                var d = findDateIn(lines[i].replace(/date/i, ' '), true);
                if (d) { return d; }
            }
        }
        return findDateIn(text, false);
    }

    function parsePayment(text) {
        var t = text.toLowerCase();
        if (/\b(cash|change\s*due|tendered|cash\s*tend)\b/.test(t)) { return 'Cash'; }
        if (/\b(visa|master\s?card|maestro|amex|american\s*express|credit\s*card|debit\s*card|card\s*payment|\bcard\b|chip|contactless|paywave|paypass)\b/.test(t)) { return 'Card'; }
        if (/\b(bank|transfer|wire|cheque|check\s*no|eft|ach|iban|online\s*payment)\b/.test(t)) { return 'Bank'; }
        return '';
    }

    function parseReference(text) {
        var m = text.match(/(?:invoice|receipt|ref(?:erence)?|inv|bill|order|trans(?:action)?)\s*(?:no\.?|number|#|:)?\s*([A-Za-z0-9][A-Za-z0-9\-\/]{2,})/i);
        return m ? m[1] : '';
    }

    function getCategoryOptions() {
        // Sourced from PHP (RECEIPT_SCAN.categories) so it stays reliable even
        // after the searchable-select widget takes over the dropdown's options.
        return (RECEIPT_SCAN.categories || []).slice();
    }

    var CATEGORY_KEYWORDS = [
        { kw: /grocer|supermarket|\bmart\b|hyper\s?market|bazaar|kirana|food\s?mart/, cat: /grocer|food|household/ },
        { kw: /restaurant|cafe|coffee|diner|bistro|pizza|burger|kitchen|bakery|\bfood\b|dine/, cat: /food|dining|restaurant|eat|meal/ },
        { kw: /fuel|petrol|diesel|gas\s*station|filling\s*station|shell|chevron|caltex|\bbp\b/, cat: /fuel|transport|vehicle|car|travel|petrol/ },
        { kw: /pharmacy|chemist|clinic|hospital|medical|drug\s?store|medic/, cat: /medical|health|pharm/ },
        { kw: /electric|wapda|k-?electric|power\s*bill|utility/, cat: /electric|utilit|bill/ },
        { kw: /water\s*(bill|board|supply)/, cat: /water|utilit|bill/ },
        { kw: /internet|broadband|telecom|mobile|airtime|recharge|phone\s*bill/, cat: /internet|phone|telecom|communicat|utilit/ },
        { kw: /uber|careem|taxi|\bbus\b|train|metro|toll|parking|transport/, cat: /transport|travel|car|vehicle/ },
        { kw: /clothing|apparel|fashion|garment|\bshoe|boutique/, cat: /cloth|apparel|fashion|shopping/ },
        { kw: /electronic|hardware|computer|mobile\s*shop|gadget/, cat: /electronic|gadget|shopping/ }
    ];

    function matchCategory(text, merchant) {
        var opts = getCategoryOptions();
        if (!opts.length) { return null; }
        var hay = (merchant + ' ' + text).toLowerCase();

        // 1) A category name appears directly in the receipt text.
        for (var i = 0; i < opts.length; i++) {
            var name = opts[i].name.toLowerCase();
            if (name.length >= 3 && hay.indexOf(name) !== -1) { return opts[i]; }
        }

        // 2) Keyword heuristics mapped to a matching category name.
        for (var k = 0; k < CATEGORY_KEYWORDS.length; k++) {
            if (CATEGORY_KEYWORDS[k].kw.test(hay)) {
                for (var j = 0; j < opts.length; j++) {
                    if (CATEGORY_KEYWORDS[k].cat.test(opts[j].name.toLowerCase())) {
                        return opts[j];
                    }
                }
            }
        }
        return null;
    }

    // Generic document headers that aren't the merchant name.
    var MERCHANT_NOISE_RE = /^(purchase\s*slip|sales?\s*(slip|receipt|invoice|memo)?|tax\s*invoice|cash\s*(memo|sales?|receipt)|receipt|invoice|bill|estimate|quotation|order|memo|thank\s*you)/i;

    function firstMerchantLine(text) {
        var lines = text.split(/\n+/);
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (line.length < 3 || line.length > 40) { continue; }
            if (MERCHANT_NOISE_RE.test(line)) { continue; }
            var letters = (line.match(/[A-Za-z]/g) || []).length;
            // A real business name has at least one proper word (4+ letters in a
            // row) and is mostly letters - skips OCR garbage like "/ RI A Ny,".
            if (letters / line.length >= 0.5 && /[A-Za-z]{4,}/.test(line)) {
                // Trim surrounding punctuation/noise from the kept name.
                return line.replace(/^[^A-Za-z0-9]+|[^A-Za-z0-9]+$/g, '');
            }
        }
        return '';
    }

    function applyResults(text) {
        var merchant = firstMerchantLine(text);
        var data = {
            expense_date: parseDate(text),
            amount: parseAmount(text),
            payment_method: parsePayment(text),
            reference: parseReference(text),
            description: merchant,
            category: matchCategory(text, merchant)
        };

        var filled = [];
        if (data.expense_date && fillField('[name="Expense[expense_date]"]', data.expense_date)) { filled.push('date'); }
        if (data.amount && fillField('#expense-amount', data.amount)) { filled.push('amount'); }
        if (data.payment_method && fillField('[name="Expense[payment_method]"]', data.payment_method)) { filled.push('payment method'); }
        if (data.reference && fillField('[name="Expense[reference]"]', data.reference)) { filled.push('reference'); }
        if (data.description && fillField('[name="Expense[description]"]', data.description)) { filled.push('description'); }
        if (data.category && fillField('#expense-category-select', data.category.id)) { filled.push('category'); }

        if (!filled.length) {
            setStatus('error', '<i class="bi bi-exclamation-circle me-1"></i>' + RECEIPT_SCAN.noData);
            return;
        }
        setStatus('success', '<i class="bi bi-check-circle me-1"></i>' + RECEIPT_SCAN.filledPrefix + ' ' + filled.join(', ') + '.');
    }

    function scanReceipt(file) {
        if (!file) { return; }

        // Skip auto-reading when the user opts out (e.g. utility bills paid on a due date).
        var $autoRead = $('#auto-read-receipt');
        if ($autoRead.length && !$autoRead.is(':checked')) { return; }

        var ext = (file.name.split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') {
            setStatus('info', '<i class="bi bi-info-circle me-1"></i>' + RECEIPT_SCAN.pdfNote);
            return;
        }
        if (['png', 'jpg', 'jpeg'].indexOf(ext) === -1) { return; }

        setStatus('loading', spinner + RECEIPT_SCAN.loadingLib);

        loadTesseract().then(function () {
            setStatus('loading', spinner + RECEIPT_SCAN.reading);
            return Tesseract.recognize(file, RECEIPT_SCAN.lang, {
                logger: function (m) {
                    if (m.status === 'recognizing text' && typeof m.progress === 'number') {
                        setStatus('loading', spinner + RECEIPT_SCAN.reading + ' ' + Math.round(m.progress * 100) + '%');
                    }
                }
            });
        }).then(function (result) {
            applyResults((result && result.data && result.data.text) ? result.data.text : '');
        }).catch(function () {
            setStatus('error', '<i class="bi bi-exclamation-circle me-1"></i>' + RECEIPT_SCAN.failed);
        });
    }

    // Scan when a file is chosen via the file picker...
    $('#expense-file-input').on('change', function () {
        if (this.files && this.files.length) {
            scanReceipt(this.files[0]);
        }
    });

    // ...and when a file is dropped onto the upload area.
    $('#upload-area').on('drop', function (e) {
        var dt = e.originalEvent && e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            scanReceipt(dt.files[0]);
        }
    });

    // Clear the status when the chosen file is removed.
    $('#remove-file').on('click', function () {
        hideStatus();
    });

    // React to toggling the auto-read option after a file is already chosen.
    $('#auto-read-receipt').on('change', function () {
        if (this.checked) {
            var input = $('#expense-file-input')[0];
            if (input && input.files && input.files.length) {
                scanReceipt(input.files[0]);
            }
        } else {
            hideStatus();
        }
    });
});
JS;

$this->registerJs($js);
