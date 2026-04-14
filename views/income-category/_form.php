<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Form Partial for Income Categories
 *
 * Renders the create/update form for income categories.
 * Used in modal dialogs via AJAX loading.
 *
 * @var yii\web\View $this
 * @var app\models\IncomeCategory $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$isNewRecord = $model->isNewRecord;

// Predefined icons for categories
$iconOptions = [
    'bi-wallet2' => 'Wallet',
    'bi-briefcase' => 'Briefcase',
    'bi-building' => 'Building',
    'bi-cash-stack' => 'Cash',
    'bi-credit-card' => 'Credit Card',
    'bi-graph-up-arrow' => 'Investment',
    'bi-gift' => 'Gift',
    'bi-piggy-bank' => 'Savings',
    'bi-currency-dollar' => 'Dollar',
    'bi-bank' => 'Bank',
    'bi-house' => 'Property',
    'bi-laptop' => 'Freelance',
    'bi-shop' => 'Business',
    'bi-award' => 'Bonus',
    'bi-percent' => 'Interest',
    'bi-folder' => 'Other',
];

// Predefined colors
$colorOptions = [
    '#16a34a' => 'Green',
    '#2563eb' => 'Blue',
    '#7c3aed' => 'Purple',
    '#db2777' => 'Pink',
    '#ea580c' => 'Orange',
    '#ca8a04' => 'Yellow',
    '#0d9488' => 'Teal',
    '#64748b' => 'Slate',
];
?>

<?php $form = ActiveForm::begin([
    'id' => 'category-form',
    'action' => $isNewRecord ? ['create'] : ['update', 'id' => $model->id],
    'options' => [
        'class' => 'needs-validation data-form income-category-form',
        'data-container' => 'income-category-pjax',
    ],
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
]); ?>

<!-- Category Name -->
<?= $form->field($model, 'name', [
    'options' => ['class' => 'mb-3'],
])->textInput([
    'maxlength' => true,
    'placeholder' => Yii::t('app', 'e.g., Salary, Freelance, Investment'),
    'class' => 'form-control',
    'autofocus' => true,
])->label(Yii::t('app', 'Category Name') . ' <span class="text-danger">*</span>') ?>

<!-- Description -->
<?= $form->field($model, 'description', [
    'options' => ['class' => 'mb-3'],
])->textarea([
    'rows' => 3,
    'placeholder' => Yii::t('app', 'Optional description for this category'),
    'class' => 'form-control',
])->label(Yii::t('app', 'Description')) ?>

<!-- Icon Selection -->
<div class="mb-3">
    <label class="form-label"><?= Yii::t('app', 'Icon') ?></label>
    <div class="icon-selector d-flex flex-wrap gap-2">
        <?php foreach ($iconOptions as $iconClass => $iconName): ?>
            <?php
            $isSelected = ($model->icon === $iconClass) || ($isNewRecord && $iconClass === 'bi-wallet2');
            ?>
            <label class="icon-option <?= $isSelected ? 'selected' : '' ?>" data-bs-toggle="tooltip" title="<?= Html::encode($iconName) ?>">
                <input type="radio"
                    name="IncomeCategory[icon]"
                    value="<?= Html::encode($iconClass) ?>"
                    <?= $isSelected ? 'checked' : '' ?>
                    class="d-none">
                <span class="icon-box">
                    <i class="bi <?= Html::encode($iconClass) ?>"></i>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- Color Selection -->
<div class="mb-3">
    <label class="form-label"><?= Yii::t('app', 'Color') ?></label>
    <div class="color-selector d-flex flex-wrap gap-2">
        <?php foreach ($colorOptions as $colorHex => $colorName): ?>
            <?php
            $isSelected = ($model->color === $colorHex) || ($isNewRecord && $colorHex === '#16a34a');
            ?>
            <label class="color-option <?= $isSelected ? 'selected' : '' ?>" data-bs-toggle="tooltip" title="<?= Html::encode($colorName) ?>">
                <input type="radio"
                    name="IncomeCategory[color]"
                    value="<?= Html::encode($colorHex) ?>"
                    <?= $isSelected ? 'checked' : '' ?>
                    class="d-none">
                <span class="color-box" style="background-color: <?= Html::encode($colorHex) ?>;"></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- Status Toggle (only for existing records) -->
<?php if (!$isNewRecord): ?>
    <div class="mb-3">
        <div class="form-check form-switch">
            <?= Html::activeCheckbox($model, 'status', [
                'class' => 'form-check-input',
                'id' => 'category-status-toggle',
                'label' => false,
            ]) ?>
            <label class="form-check-label" for="category-status-toggle">
                <?= Yii::t('app', 'Active') ?>
            </label>
        </div>
        <small class="text-muted">
            <?= Yii::t('app', 'Inactive categories won\'t appear in income form dropdowns') ?>
        </small>
    </div>
<?php endif; ?>

<!-- Preview -->
<div class="mb-4 p-3 bg-light rounded">
    <label class="form-label small text-muted mb-2"><?= Yii::t('app', 'Preview') ?></label>
    <div class="d-flex align-items-center gap-3" id="category-preview">
        <div class="category-icon-preview"
            style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 1.5rem;">
            <i class="bi bi-wallet2"></i>
        </div>
        <div>
            <div class="fw-semibold" id="preview-name"><?= $model->name ?: Yii::t('app', 'Category Name') ?></div>
            <small class="text-muted" id="preview-description"><?= $model->description ?: Yii::t('app', 'Description') ?></small>
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="d-flex gap-2 justify-content-end border-top pt-3">
    <?= Html::button(
        Yii::t('app', 'Cancel'),
        [
            'class' => 'btn btn-light',
            'data-bs-dismiss' => 'modal',
        ]
    ) ?>
    <?= Html::submitButton(
        $isNewRecord
            ? '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Create Category')
            : '<i class="bi bi-check-lg me-1"></i>' . Yii::t('app', 'Save Changes'),
        [
            'class' => $isNewRecord ? 'btn btn-primary' : 'btn btn-success',
        ]
    ) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
// Register scripts for this form
$js = <<<JS
(function() {
    'use strict';

    var form = document.getElementById('category-form');
    if (!form) return;

    var nameInput = form.querySelector('[name="IncomeCategory[name]"]');
    var descInput = form.querySelector('[name="IncomeCategory[description]"]');
    var iconInputs = form.querySelectorAll('[name="IncomeCategory[icon]"]');
    var colorInputs = form.querySelectorAll('[name="IncomeCategory[color]"]');
    var previewIcon = document.querySelector('#category-preview .category-icon-preview i');
    var previewIconBox = document.querySelector('#category-preview .category-icon-preview');
    var previewName = document.getElementById('preview-name');
    var previewDesc = document.getElementById('preview-description');

    // Icon selection
    form.querySelectorAll('.icon-option').forEach(function(option) {
        option.addEventListener('click', function() {
            form.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            updatePreview();
        });
    });

    // Color selection
    form.querySelectorAll('.color-option').forEach(function(option) {
        option.addEventListener('click', function() {
            form.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            updatePreview();
        });
    });

    // Name/Description input
    if (nameInput) {
        nameInput.addEventListener('input', updatePreview);
    }
    if (descInput) {
        descInput.addEventListener('input', updatePreview);
    }

    function updatePreview() {
        // Update icon
        var selectedIcon = form.querySelector('[name="IncomeCategory[icon]"]:checked');
        if (selectedIcon && previewIcon) {
            previewIcon.className = 'bi ' + selectedIcon.value;
        }

        // Update color
        var selectedColor = form.querySelector('[name="IncomeCategory[color]"]:checked');
        if (selectedColor && previewIconBox) {
            var color = selectedColor.value;
            previewIconBox.style.backgroundColor = color + '20';
            previewIconBox.style.color = color;
        }

        // Update text
        if (previewName && nameInput) {
            previewName.textContent = nameInput.value || 'Category Name';
        }
        if (previewDesc && descInput) {
            previewDesc.textContent = descInput.value || 'Description';
        }
    }

    // Initial preview update
    updatePreview();

    // Initialize tooltips
    form.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
})();
JS;

$this->registerJs($js);
