<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Form Partial for Expense Categories
 *
 * Renders the create/update form for expense categories with parent selection,
 * icon picker, and color picker.
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseCategory $model
 * @var array $parentOptions Parent category dropdown options
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$isNewRecord = $model->isNewRecord;

// Predefined icons for expense categories
$iconOptions = [
    'bi-cart' => 'Shopping',
    'bi-basket' => 'Groceries',
    'bi-cup-hot' => 'Food & Drink',
    'bi-car-front' => 'Transport',
    'bi-fuel-pump' => 'Fuel',
    'bi-house' => 'Housing',
    'bi-lightning' => 'Utilities',
    'bi-wifi' => 'Internet',
    'bi-phone' => 'Phone',
    'bi-heart-pulse' => 'Healthcare',
    'bi-capsule' => 'Medicine',
    'bi-book' => 'Education',
    'bi-controller' => 'Entertainment',
    'bi-film' => 'Movies',
    'bi-music-note' => 'Music',
    'bi-airplane' => 'Travel',
    'bi-gift' => 'Gifts',
    'bi-scissors' => 'Personal Care',
    'bi-shirt' => 'Clothing',
    'bi-tools' => 'Maintenance',
    'bi-shield-check' => 'Insurance',
    'bi-bank' => 'Banking',
    'bi-credit-card' => 'Credit Card',
    'bi-cash-stack' => 'Cash',
    'bi-briefcase' => 'Business',
    'bi-printer' => 'Office',
    'bi-pc-display' => 'Electronics',
    'bi-wrench' => 'Repairs',
    'bi-droplet' => 'Water',
    'bi-tree' => 'Garden',
    'bi-paw' => 'Pets',
    'bi-baby' => 'Kids',
    'bi-folder' => 'Other',
];

// Predefined colors for expense categories
$colorOptions = [
    '#dc2626' => 'Red',
    '#ea580c' => 'Orange',
    '#ca8a04' => 'Yellow',
    '#16a34a' => 'Green',
    '#0d9488' => 'Teal',
    '#2563eb' => 'Blue',
    '#7c3aed' => 'Purple',
    '#db2777' => 'Pink',
    '#64748b' => 'Slate',
    '#1f2937' => 'Dark',
];
?>

<div class="expense-categories-form">
    <?php $form = ActiveForm::begin([
        'id' => 'expense-category-form',
        'action' => $isNewRecord
            ? ['create', 'parent' => $model->parent_id]
            : ['update', 'id' => $model->id],
        'enableAjaxValidation' => false,
        'enableClientValidation' => true,
    ]); ?>

    <!-- Parent Category -->
    <div class="mb-3">
        <?= $form->field($model, 'parent_id')->dropDownList(
            ['' => Yii::t('app', '— Root Category (No Parent) —')] + $parentOptions,
            [
                'class' => 'form-select',
                'id' => 'parent-category-select',
            ]
        )->label(Yii::t('app', 'Parent Category')) ?>
        <small class="text-muted">
            <?= Yii::t('app', 'Leave empty to create a root-level category, or select a parent to create a subcategory.') ?>
        </small>
    </div>

    <!-- Category Name -->
    <?= $form->field($model, 'name', [
        'options' => ['class' => 'mb-3'],
    ])->textInput([
        'maxlength' => true,
        'placeholder' => Yii::t('app', 'e.g., Groceries, Transportation, Entertainment'),
        'class' => 'form-control',
        'autofocus' => true,
    ])->label(Yii::t('app', 'Category Name') . ' <span class="text-danger">*</span>') ?>

    <!-- Description -->
    <?= $form->field($model, 'description', [
        'options' => ['class' => 'mb-3'],
    ])->textarea([
        'rows' => 2,
        'placeholder' => Yii::t('app', 'Optional description for this category'),
        'class' => 'form-control',
    ])->label(Yii::t('app', 'Description')) ?>

    <!-- Icon Selection -->
    <div class="mb-3">
        <label class="form-label"><?= Yii::t('app', 'Icon') ?></label>
        <div class="icon-selector d-flex flex-wrap gap-2" style="max-height: 200px; overflow-y: auto;">
            <?php foreach ($iconOptions as $iconClass => $iconName): ?>
                <?php
                $isSelected = ($model->icon === $iconClass) || ($isNewRecord && !$model->icon && $iconClass === 'bi-folder');
                ?>
                <label class="icon-option <?= $isSelected ? 'selected' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= Html::encode($iconName) ?>">
                    <input type="radio"
                        name="ExpenseCategory[icon]"
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
                $isSelected = ($model->color === $colorHex) || ($isNewRecord && !$model->color && $colorHex === '#dc2626');
                ?>
                <label class="color-option <?= $isSelected ? 'selected' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= Html::encode($colorName) ?>">
                    <input type="radio"
                        name="ExpenseCategory[color]"
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
                <?= Yii::t('app', 'Inactive categories won\'t appear in expense form dropdowns') ?>
            </small>
        </div>
    <?php endif; ?>

    <!-- Preview -->
    <div class="mb-4 p-3 bg-light rounded">
        <label class="form-label small text-muted mb-2"><?= Yii::t('app', 'Preview') ?></label>
        <div class="d-flex align-items-center gap-3" id="category-preview">
            <div class="category-icon-preview"
                style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 1.5rem;">
                <i class="bi <?= Html::encode($model->icon ?: 'bi-folder') ?>"></i>
            </div>
            <div>
                <div class="fw-semibold" id="preview-name"><?= Html::encode($model->name) ?: Yii::t('app', 'Category Name') ?></div>
                <small class="text-muted" id="preview-path">
                    <?php if ($model->parent_id): ?>
                        <?= Html::encode($model->parent->name ?? '') ?> › <span id="preview-name-path"><?= Html::encode($model->name) ?: Yii::t('app', 'Category Name') ?></span>
                    <?php else: ?>
                        <span id="preview-parent-name"><?= Yii::t('app', 'Root Category') ?></span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Hierarchy Info (for existing records with children) -->
    <?php if (!$isNewRecord && $model->hasChildren()): ?>
        <div class="alert alert-info py-2 mb-3">
            <i class="bi bi-info-circle me-1"></i>
            <?= Yii::t('app', 'This category has {count} subcategory(s). Moving this category will also move all its subcategories.', [
                'count' => count($model->children),
            ]) ?>
        </div>
    <?php endif; ?>

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
                'class' => $isNewRecord ? 'btn btn-danger' : 'btn btn-success',
            ]
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
// Register inline styles
$this->registerCss(<<<CSS
    .expense-categories-form .icon-option {
        cursor: pointer;
    }
    .expense-categories-form .icon-option .icon-box {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--em-gray-200, #e5e7eb);
        border-radius: 8px;
        font-size: 1.125rem;
        color: var(--em-gray-500, #6b7280);
        background-color: var(--em-white, #fff);
        transition: all 0.15s ease;
    }
    .expense-categories-form .icon-option:hover .icon-box {
        border-color: var(--em-danger, #dc2626);
        color: var(--em-danger, #dc2626);
        background-color: rgba(220, 38, 38, 0.05);
    }
    .expense-categories-form .icon-option.selected .icon-box {
        border-color: var(--em-danger, #dc2626);
        color: var(--em-danger, #dc2626);
        background-color: var(--em-danger-light, #fee2e2);
    }
    .expense-categories-form .color-option {
        cursor: pointer;
    }
    .expense-categories-form .color-option .color-box {
        width: 32px;
        height: 32px;
        display: block;
        border-radius: 50%;
        border: 3px solid transparent;
        transition: all 0.15s ease;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
    }
    .expense-categories-form .color-option:hover .color-box {
        transform: scale(1.15);
    }
    .expense-categories-form .color-option.selected .color-box {
        border-color: var(--em-gray-800, #1f2937);
        box-shadow: 0 0 0 2px var(--em-white, #fff), 0 0 0 4px var(--em-gray-800, #1f2937);
    }
    .expense-categories-form .category-icon-preview {
        transition: all 0.15s ease;
    }
    .expense-categories-form .icon-selector::-webkit-scrollbar {
        width: 6px;
    }
    .expense-categories-form .icon-selector::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .expense-categories-form .icon-selector::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }
    .expense-categories-form .icon-selector::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
CSS);

// Register inline JavaScript
$parentOptionsJson = \yii\helpers\Json::encode($parentOptions);
$js = <<<JS
(function() {
    'use strict';

    var form = document.getElementById('expense-category-form');
    if (!form) return;

    var nameInput = form.querySelector('[name="ExpenseCategory[name]"]');
    var descInput = form.querySelector('[name="ExpenseCategory[description]"]');
    var parentSelect = form.querySelector('[name="ExpenseCategory[parent_id]"]');
    var previewIcon = document.querySelector('#category-preview .category-icon-preview i');
    var previewIconBox = document.querySelector('#category-preview .category-icon-preview');
    var previewName = document.getElementById('preview-name');
    var previewPath = document.getElementById('preview-path');
    var parentOptions = {$parentOptionsJson};

    // Icon selection
    form.querySelectorAll('.icon-option').forEach(function(option) {
        option.addEventListener('click', function() {
            form.querySelectorAll('.icon-option').forEach(function(o) {
                o.classList.remove('selected');
            });
            this.classList.add('selected');
            updatePreview();
        });
    });

    // Color selection
    form.querySelectorAll('.color-option').forEach(function(option) {
        option.addEventListener('click', function() {
            form.querySelectorAll('.color-option').forEach(function(o) {
                o.classList.remove('selected');
            });
            this.classList.add('selected');
            updatePreview();
        });
    });

    // Name input
    if (nameInput) {
        nameInput.addEventListener('input', updatePreview);
    }

    // Parent select change
    if (parentSelect) {
        parentSelect.addEventListener('change', updatePreview);
    }

    function updatePreview() {
        // Update icon
        var selectedIcon = form.querySelector('[name="ExpenseCategory[icon]"]:checked');
        if (selectedIcon && previewIcon) {
            previewIcon.className = 'bi ' + selectedIcon.value;
        }

        // Update color
        var selectedColor = form.querySelector('[name="ExpenseCategory[color]"]:checked');
        if (selectedColor && previewIconBox) {
            var color = selectedColor.value;
            previewIconBox.style.backgroundColor = color + '20';
            previewIconBox.style.color = color;
        }

        // Update name
        var name = nameInput ? nameInput.value : '';
        if (previewName) {
            previewName.textContent = name || 'Category Name';
        }

        // Update path
        if (previewPath && parentSelect) {
            var parentId = parentSelect.value;
            var parentName = '';

            if (parentId && parentOptions[parentId]) {
                // Remove indentation dashes from parent name
                parentName = parentOptions[parentId].replace(/^[—\s]+/, '');
            }

            if (parentName) {
                previewPath.innerHTML = '<span class="text-muted">' + escapeHtml(parentName) + '</span> › ' + escapeHtml(name || 'Category Name');
            } else {
                previewPath.innerHTML = '<span class="text-muted">Root Category</span>';
            }
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
?>
