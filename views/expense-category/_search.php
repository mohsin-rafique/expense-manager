<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Search Form Partial for Expense Categories
 *
 * Professional search interface with:
 * - Search input field (matches name and description)
 * - Status filter dropdown
 * - Per-page selector
 * - Form submits via standard GET request (data-pjax="0")
 *
 * The active view (tree or list) rides along in a hidden field so a search
 * does not bounce the user back to the tree.
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseCategorySearch $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

// Get current per-page value
$perPage = Yii::$app->request->get('per-page', 25);
?>

<div class="expense-category-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'id' => 'expense-category-search-form',
        'options' => [
            'data-pjax' => '0',
            'class' => 'row g-3 align-items-end',
        ],
    ]); ?>

    <?= Html::activeHiddenInput($model, 'viewType', ['id' => 'category-view-type']) ?>

    <!-- Search Input -->
    <div class="col-12 col-md-6 col-lg-4">
        <label class="form-label small text-muted mb-1" for="category-search-input">
            <i class="bi bi-search me-1"></i><?= Yii::t('app', 'Search') ?>
        </label>
        <?= Html::activeTextInput($model, 'globalSearch', [
            'class' => 'form-control',
            'placeholder' => Yii::t('app', 'Search by name or description...'),
            'id' => 'category-search-input',
        ]) ?>
    </div>

    <!-- Status Filter -->
    <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small text-muted mb-1" for="category-status-filter">
            <i class="bi bi-funnel me-1"></i><?= Yii::t('app', 'Status') ?>
        </label>
        <?= Html::activeDropDownList($model, 'status', \app\models\ExpenseCategorySearch::getStatusOptions(), [
            'class' => 'form-select',
            'id' => 'category-status-filter',
        ]) ?>
    </div>

    <!-- Per Page Selector -->
    <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small text-muted mb-1" for="per-page-selector">
            <i class="bi bi-list-ol me-1"></i><?= Yii::t('app', 'Show') ?>
        </label>
        <?= Html::dropDownList('per-page', $perPage, [
            '10' => '10 ' . Yii::t('app', 'entries'),
            '25' => '25 ' . Yii::t('app', 'entries'),
            '50' => '50 ' . Yii::t('app', 'entries'),
            '100' => '100 ' . Yii::t('app', 'entries'),
        ], [
            'class' => 'form-select',
            'id' => 'per-page-selector',
        ]) ?>
    </div>

    <!-- Action Buttons -->
    <div class="col-12 col-lg-4">
        <div class="d-flex gap-2 justify-content-lg-end">
            <?= Html::submitButton(
                '<i class="bi bi-search me-1"></i>' . Yii::t('app', 'Search'),
                ['class' => 'btn btn-danger']
            ) ?>
            <?= Html::a(
                '<i class="bi bi-arrow-counterclockwise me-1"></i>' . Yii::t('app', 'Reset'),
                ['index'],
                [
                    'class' => 'btn btn-outline-secondary',
                    'data-pjax' => '0',
                ]
            ) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
