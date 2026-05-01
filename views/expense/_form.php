<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
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
                'class' => 'form-select',
                'prompt' => Yii::t('app', '— Select Category —'),
                'id' => 'expense-category-select',
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
                    'class' => 'form-select',
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
                'class' => 'form-select',
                'prompt' => Yii::t('app', '— Select Payment Method —'),
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
$js = <<<JS
$(function() {
    console.log('Form JS loaded');
    console.log('NEM object:', typeof NEM);
    console.log('Form element:', $('.data-form').length);

    // Test if form submission is being captured
    $('.data-form').on('submit', function(e) {
        console.log('Form submit triggered');
    });

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
});
JS;

$this->registerJs($js);
