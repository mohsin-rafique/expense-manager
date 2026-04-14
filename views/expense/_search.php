<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Search Form Partial for Expenses
 *
 * Advanced filtering form with date range picker, category selection,
 * payment method filter, and text search capabilities.
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseSearch $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\ExpenseCategory;
use app\models\Expense;

$categories = ExpenseCategory::getExpenseCategoryHierarchy();
?>

<div class="expense-search">
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
    <div class="col-md-2 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-folder me-1"></i>
            <?= Yii::t('app', 'Category') ?>
        </label>
        <?= Html::activeDropDownList($model, 'expense_category_id', $categories, [
            'class' => 'form-select',
            'prompt' => Yii::t('app', 'All Categories'),
        ]) ?>
    </div>

    <!-- Payment Method -->
    <div class="col-md-2 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-credit-card me-1"></i>
            <?= Yii::t('app', 'Payment') ?>
        </label>
        <?= Html::activeDropDownList($model, 'payment_method', Expense::getPaymentMethods(), [
            'class' => 'form-select',
            'prompt' => Yii::t('app', 'All Methods'),
        ]) ?>
    </div>

    <!-- Search -->
    <div class="col-md-2 col-sm-6">
        <label class="form-label small text-muted">
            <i class="bi bi-search me-1"></i>
            <?= Yii::t('app', 'Search') ?>
        </label>
        <?= Html::activeTextInput($model, 's', [
            'class' => 'form-control',
            'placeholder' => Yii::t('app', 'Reference, desc...'),
        ]) ?>
    </div>

    <!-- Actions -->
    <div class="col-md-2 col-sm-6">
        <div class="d-flex gap-2">
            <?= Html::submitButton(
                '<i class="bi bi-funnel me-1"></i>' . Yii::t('app', 'Filter'),
                ['class' => 'btn btn-danger']
            ) ?>
            <?= Html::a(
                '<i class="bi bi-x-lg me-1"></i>' . Yii::t('app', 'Reset'),
                ['index'],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </div>

    <!-- Quick Filters & Advanced Toggle Row -->
    <div class="col-12">
        <div class="d-flex flex-wrap gap-2 pt-3 border-top mt-2 pb-3 align-items-center">
            <span class="text-muted small me-2"><?= Yii::t('app', 'Quick:') ?></span>
            <button type="button" class="btn btn-outline-danger btn-sm quick-date" data-range="today">
                <?= Yii::t('app', 'Today') ?>
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm quick-date" data-range="week">
                <?= Yii::t('app', 'This Week') ?>
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm quick-date" data-range="month">
                <?= Yii::t('app', 'This Month') ?>
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm quick-date" data-range="year">
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
            <!-- Amount -->
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted">
                    <i class="bi bi-currency-dollar me-1"></i>
                    <?= Yii::t('app', 'Amount') ?>
                </label>
                <?= Html::activeTextInput($model, 'amount', [
                    'class' => 'form-control',
                    'placeholder' => Yii::t('app', 'Exact amount...'),
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
            <div class="col-md-6 col-sm-12">
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
