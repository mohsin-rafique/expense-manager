<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Search Form Partial for Incomes
 *
 * Advanced filtering form with date range picker, category selection,
 * quick date filters, and text search capabilities.
 *
 * @var yii\web\View $this
 * @var app\models\IncomeSearch $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\IncomeCategory;

$categories = IncomeCategory::getIncomeCategory();
?>

<div class="income-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'class' => 'row g-3 align-items-end',
            'data-pjax' => 1,
        ],
    ]); ?>

    <!-- Date Range -->
    <div class="col-md-2 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-calendar-range me-1"></i>
            <?= Yii::t('app', 'From Date') ?>
        </label>
        <?= Html::activeInput('date', $model, 'start_date', [
            'class' => 'form-control',
            'placeholder' => Yii::t('app', 'Start date'),
        ]) ?>
    </div>

    <div class="col-md-2 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-calendar-range me-1"></i>
            <?= Yii::t('app', 'To Date') ?>
        </label>
        <?= Html::activeInput('date', $model, 'end_date', [
            'class' => 'form-control',
            'placeholder' => Yii::t('app', 'End date'),
        ]) ?>
    </div>

    <!-- Category -->
    <div class="col-md-3 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-folder me-1"></i>
            <?= Yii::t('app', 'Category') ?>
        </label>
        <?= Html::activeDropDownList($model, 'income_category_id', $categories, [
            'class' => 'form-select',
            'prompt' => Yii::t('app', 'All Categories'),
        ]) ?>
    </div>

    <!-- Search -->
    <div class="col-md-3 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-search me-1"></i>
            <?= Yii::t('app', 'Search') ?>
        </label>
        <?= Html::activeTextInput($model, 's', [
            'class' => 'form-control',
            'placeholder' => Yii::t('app', 'Reference, description...'),
        ]) ?>
    </div>

    <!-- Actions -->
    <div class="col-md-2 col-sm-6">
        <div class="d-flex gap-2">
            <?= Html::submitButton(
                '<i class="bi bi-funnel me-1"></i>' . Yii::t('app', 'Filter'),
                ['class' => 'btn btn-success']
            ) ?>
            <?= Html::a(
                '<i class="bi bi-x-lg"></i>',
                ['index'],
                ['class' => 'btn btn-outline-secondary', 'title' => Yii::t('app', 'Reset')]
            ) ?>
        </div>
    </div>

    <!-- Quick Filters & Advanced Toggle Row -->
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2 pt-3 border-top mt-2 align-items-center">
            <span class="text-muted small me-2"><?= Yii::t('app', 'Quick:') ?></span>
            <button type="button" class="btn btn-outline-success btn-sm quick-date" data-range="today">
                <?= Yii::t('app', 'Today') ?>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm quick-date" data-range="week">
                <?= Yii::t('app', 'This Week') ?>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm quick-date" data-range="month">
                <?= Yii::t('app', 'This Month') ?>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm quick-date" data-range="last_month">
                <?= Yii::t('app', 'Last Month') ?>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm quick-date" data-range="year">
                <?= Yii::t('app', 'This Year') ?>
            </button>

            <!-- Advanced Toggle -->
            <button type="button"
                class="btn btn-link text-muted btn-sm ms-auto p-0"
                data-bs-toggle="collapse"
                data-bs-target="#advanced-filters">
                <i class="bi bi-sliders me-1"></i>
                <?= Yii::t('app', 'More Filters') ?>
            </button>
        </div>
    </div>

    <!-- Advanced Filters (Collapsed) -->
    <div class="col-12 collapse" id="advanced-filters">
        <div class="row g-3 pt-3 border-top">
            <!-- Min Amount -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted">
                    <i class="bi bi-currency-dollar me-1"></i>
                    <?= Yii::t('app', 'Min Amount') ?>
                </label>
                <?= Html::activeTextInput($model, 'amount_min', [
                    'class' => 'form-control',
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00',
                ]) ?>
            </div>

            <!-- Max Amount -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted">
                    <i class="bi bi-currency-dollar me-1"></i>
                    <?= Yii::t('app', 'Max Amount') ?>
                </label>
                <?= Html::activeTextInput($model, 'amount_max', [
                    'class' => 'form-control',
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00',
                ]) ?>
            </div>

            <!-- Reference -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted">
                    <i class="bi bi-hash me-1"></i>
                    <?= Yii::t('app', 'Reference') ?>
                </label>
                <?= Html::activeTextInput($model, 'reference', [
                    'class' => 'form-control',
                    'placeholder' => Yii::t('app', 'Invoice, receipt #...'),
                ]) ?>
            </div>

            <!-- Description -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted">
                    <i class="bi bi-text-paragraph me-1"></i>
                    <?= Yii::t('app', 'Description') ?>
                </label>
                <?= Html::activeTextInput($model, 'description', [
                    'class' => 'form-control',
                    'placeholder' => Yii::t('app', 'Search in description...'),
                ]) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
(function() {
    'use strict';

    // Quick date range buttons
    document.querySelectorAll('.income-search .quick-date').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var range = this.dataset.range;
            var today = new Date();
            var startDate, endDate;

            switch(range) {
                case 'today':
                    startDate = endDate = today;
                    break;
                case 'week':
                    var day = today.getDay();
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - day);
                    endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 6);
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'last_month':
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'year':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = new Date(today.getFullYear(), 11, 31);
                    break;
            }

            function formatDate(date) {
                return date.getFullYear() + '-' +
                       String(date.getMonth() + 1).padStart(2, '0') + '-' +
                       String(date.getDate()).padStart(2, '0');
            }

            // Update date inputs
            var startInput = document.querySelector('[name="IncomeSearch[start_date]"]');
            var endInput = document.querySelector('[name="IncomeSearch[end_date]"]');

            if (startInput) startInput.value = formatDate(startDate);
            if (endInput) endInput.value = formatDate(endDate);

            // Update button states
            document.querySelectorAll('.income-search .quick-date').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');

            // Auto-submit form
            this.closest('form').submit();
        });
    });

    // Trim whitespace on form submit
    var searchForm = document.querySelector('.income-search form');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            this.querySelectorAll('input[type="text"]').forEach(function(input) {
                input.value = input.value.trim();
            });
        });
    }
})();
JS;

$this->registerJs($js);

$this->registerCss(<<<CSS
    .income-search .form-label {
        font-weight: 500;
        margin-bottom: 0.375rem;
    }
    .income-search .form-control,
    .income-search .form-select {
        border-radius: 8px;
        border-color: #e2e8f0;
        padding: 0.5rem 0.875rem;
    }
    .income-search .form-control:focus,
    .income-search .form-select:focus {
        border-color: var(--em-success, #16a34a);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }
    .income-search .quick-date {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
    }
    .income-search .quick-date.active {
        background-color: var(--em-success, #16a34a);
        border-color: var(--em-success, #16a34a);
        color: #fff;
    }
CSS);
?>
