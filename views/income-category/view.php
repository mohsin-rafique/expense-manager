<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * View Partial for Income Categories
 *
 * Displays detailed information about a single income category.
 * Used in modal dialogs via AJAX loading.
 *
 * @var yii\web\View $this
 * @var app\models\IncomeCategory $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$icon = $model->icon ?? 'bi-folder';
$color = $model->color ?? '#16a34a';
$incomeCount = $model->getIncomes()->count();
$totalIncome = $model->getIncomes()->sum('amount') ?? 0;
?>

<div class="income-category-view">

    <!-- Category Header -->
    <div class="text-center mb-4 pb-4 border-bottom">
        <div class="category-icon-large mx-auto mb-3"
            style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;
                    border-radius: 16px; font-size: 2.5rem;
                    background-color: <?= Html::encode($color) ?>20; color: <?= Html::encode($color) ?>;">
            <i class="bi <?= Html::encode($icon) ?>"></i>
        </div>
        <h4 class="mb-1"><?= Html::encode($model->name) ?></h4>
        <?php if ($model->status): ?>
            <span class="badge bg-success-subtle text-success">
                <i class="bi bi-check-circle me-1"></i><?= Yii::t('app', 'Active') ?>
            </span>
        <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary">
                <i class="bi bi-x-circle me-1"></i><?= Yii::t('app', 'Inactive') ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Description -->
    <?php if ($model->description): ?>
        <div class="mb-4">
            <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Description') ?></label>
            <p class="mb-0"><?= Html::encode($model->description) ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="bg-light rounded p-3 text-center">
                <div class="text-muted small mb-1"><?= Yii::t('app', 'Total Records') ?></div>
                <div class="h4 mb-0 text-primary"><?= Yii::$app->formatter->asInteger($incomeCount) ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="bg-light rounded p-3 text-center">
                <div class="text-muted small mb-1"><?= Yii::t('app', 'Total Income') ?></div>
                <div class="h4 mb-0 text-success"><?= Yii::$app->formatter->asDecimal($totalIncome, 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Meta Information -->
    <div class="row g-3 text-muted small">
        <div class="col-6">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-plus"></i>
                <div>
                    <div class="text-muted"><?= Yii::t('app', 'Created') ?></div>
                    <div class="text-dark"><?= Yii::$app->formatter->asDatetime($model->created_at, 'medium') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check"></i>
                <div>
                    <div class="text-muted"><?= Yii::t('app', 'Updated') ?></div>
                    <div class="text-dark"><?= Yii::$app->formatter->asDatetime($model->updated_at, 'medium') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-4">
        <?= Html::button(
            Yii::t('app', 'Close'),
            [
                'class' => 'btn btn-light',
                'data-bs-dismiss' => 'modal',
            ]
        ) ?>
        <?= Html::button(
            '<i class="bi bi-pencil me-1"></i>' . Yii::t('app', 'Edit'),
            [
                'class' => 'btn btn-primary btn-modal',
                'data-url' => Url::to(['update', 'id' => $model->id]),
                'data-icon' => '<i class="bi bi-folder text-success me-2"></i>',
                'data-title' => Yii::t('app', 'Update Category'),
            ]
        ) ?>
    </div>
</div>
