<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * View Partial for Expense Categories
 *
 * Displays detailed information about a single expense category including
 * hierarchy, statistics, and child categories.
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseCategory $model
 * @var array $children Child categories
 * @var int $expenseCount Number of expenses in this category
 * @var float $totalExpense Total expense amount
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$icon = $model->icon ?? 'bi-folder';
$color = $model->color ?? '#dc2626';
$depth = $model->getDepth();
$breadcrumbPath = $model->getBreadcrumbPath();
?>

<div class="expense-category-view">

    <!-- Category Header -->
    <div class="text-center mb-4 pb-4 border-bottom">
        <div class="category-icon-large mx-auto mb-3"
            style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;
                    border-radius: 16px; font-size: 2.5rem;
                    background-color: <?= Html::encode($color) ?>20; color: <?= Html::encode($color) ?>;">
            <i class="bi <?= Html::encode($icon) ?>"></i>
        </div>
        <h4 class="mb-2"><?= Html::encode($model->name) ?></h4>

        <!-- Breadcrumb Path -->
        <?php if ($depth > 0): ?>
            <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-2">
                <ol class="breadcrumb breadcrumb-sm mb-0">
                    <?php foreach ($breadcrumbPath as $index => $pathItem): ?>
                        <?php if ($index === count($breadcrumbPath) - 1): ?>
                            <li class="breadcrumb-item active"><?= Html::encode($pathItem) ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item text-muted"><?= Html::encode($pathItem) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <!-- Status Badge -->
        <?php if ($model->status): ?>
            <span class="badge bg-success-subtle text-success">
                <i class="bi bi-check-circle me-1"></i><?= Yii::t('app', 'Active') ?>
            </span>
        <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary">
                <i class="bi bi-x-circle me-1"></i><?= Yii::t('app', 'Inactive') ?>
            </span>
        <?php endif; ?>

        <?php if ($depth > 0): ?>
            <span class="badge bg-light text-dark ms-1">
                <i class="bi bi-diagram-3 me-1"></i><?= Yii::t('app', 'Level {depth}', ['depth' => $depth + 1]) ?>
            </span>
        <?php else: ?>
            <span class="badge bg-light text-dark ms-1">
                <i class="bi bi-folder me-1"></i><?= Yii::t('app', 'Root Category') ?>
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
                <div class="text-muted small mb-1"><?= Yii::t('app', 'Direct Expenses') ?></div>
                <div class="h4 mb-0 text-primary"><?= Yii::$app->formatter->asInteger($expenseCount) ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="bg-light rounded p-3 text-center">
                <div class="text-muted small mb-1"><?= Yii::t('app', 'Total Amount') ?></div>
                <div class="h4 mb-0 text-danger"><?= Yii::$app->formatter->asDecimal($totalExpense, 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Subcategories -->
    <?php if (!empty($children)): ?>
        <div class="mb-4">
            <label class="form-label small text-muted mb-2">
                <i class="bi bi-diagram-3 me-1"></i>
                <?= Yii::t('app', 'Subcategories ({count})', ['count' => count($children)]) ?>
            </label>
            <div class="list-group list-group-flush">
                <?php foreach ($children as $child): ?>
                    <div class="list-group-item d-flex align-items-center px-0 py-2 <?= !$child->status ? 'opacity-50' : '' ?>">
                        <span class="me-2" style="color: <?= Html::encode($child->color ?? $color) ?>;">
                            <i class="bi <?= Html::encode($child->icon ?? 'bi-folder') ?>"></i>
                        </span>
                        <span class="flex-grow-1">
                            <?= Html::encode($child->name) ?>
                            <?php if ($child->hasChildren()): ?>
                                <span class="badge bg-light text-muted ms-1" style="font-size: 0.7rem;">
                                    +<?= count($child->children) ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <?php if (!$child->status): ?>
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">
                                <?= Yii::t('app', 'Inactive') ?>
                            </span>
                        <?php endif; ?>
                        <div class="ms-2">
                            <a href="#"
                                class="btn btn-sm btn-link p-0 text-muted"
                                onclick="viewCategory(<?= $child->id ?>); return false;"
                                title="<?= Yii::t('app', 'View') ?>">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Parent Category -->
    <?php if ($model->parent): ?>
        <div class="mb-4">
            <label class="form-label small text-muted mb-2">
                <i class="bi bi-arrow-up-circle me-1"></i>
                <?= Yii::t('app', 'Parent Category') ?>
            </label>
            <div class="d-flex align-items-center p-2 bg-light rounded">
                <span class="me-2" style="color: <?= Html::encode($model->parent->color ?? '#dc2626') ?>;">
                    <i class="bi <?= Html::encode($model->parent->icon ?? 'bi-folder') ?>"></i>
                </span>
                <span class="flex-grow-1"><?= Html::encode($model->parent->name) ?></span>
                <a href="#"
                    class="btn btn-sm btn-link p-0"
                    onclick="viewCategory(<?= $model->parent->id ?>); return false;">
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Meta Information -->
    <div class="row g-3 text-muted small mb-4">
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
    <div class="d-flex gap-2 justify-content-between border-top pt-3">
        <div>
            <?= Html::a(
                '<i class="bi bi-plus-circle me-1"></i>' . Yii::t('app', 'Add Subcategory'),
                '#',
                [
                    'class' => 'btn btn-outline-danger btn-sm',
                    'onclick' => 'createSubcategory(' . $model->id . '); return false;',
                ]
            ) ?>
        </div>
        <div class="d-flex gap-2">
            <?= Html::button(
                Yii::t('app', 'Close'),
                [
                    'class' => 'btn btn-light',
                    'data-bs-dismiss' => 'modal',
                ]
            ) ?>
            <?= Html::a(
                '<i class="bi bi-pencil me-1"></i>' . Yii::t('app', 'Edit'),
                '#',
                [
                    'class' => 'btn btn-danger',
                    'onclick' => 'editCategory(' . $model->id . '); return false;',
                ]
            ) ?>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
    .expense-category-view .breadcrumb-sm {
        font-size: 0.8rem;
    }
    .expense-category-view .breadcrumb-sm .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
    }
    .expense-category-view .list-group-item {
        border-left: none;
        border-right: none;
    }
    .expense-category-view .list-group-item:first-child {
        border-top: none;
    }
CSS);
