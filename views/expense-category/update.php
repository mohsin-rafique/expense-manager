<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Update View for Expense Categories
 *
 * Wrapper view for editing an existing expense category.
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseCategory $model
 * @var array $parentOptions Parent category dropdown options
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\bootstrap5\Breadcrumbs;

$this->title = Yii::t('app', 'Update: {name}', ['name' => $model->name]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Expense Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>

<div class="expense-categories-update">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <nav aria-label="breadcrumb">
                <?= Breadcrumbs::widget([
                    'links' => $this->params['breadcrumbs'],
                    'options' => ['class' => 'breadcrumb mb-0'],
                ]) ?>
            </nav>
        </div>
        <div>
            <?= Html::a(
                '<i class="bi bi-arrow-left me-1"></i>' . Yii::t('app', 'Back to List'),
                ['index'],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </div>

    <!-- Breadcrumb Path -->
    <?php if ($model->getDepth() > 0): ?>
        <div class="mb-3">
            <nav aria-label="category-path">
                <ol class="breadcrumb bg-light p-2 rounded">
                    <?php foreach ($model->getBreadcrumbPath() as $index => $pathItem): ?>
                        <?php if ($index === count($model->getBreadcrumbPath()) - 1): ?>
                            <li class="breadcrumb-item active">
                                <i class="bi <?= Html::encode($model->icon ?? 'bi-folder') ?> me-1" style="color: <?= Html::encode($model->color ?? '#dc2626') ?>;"></i>
                                <?= Html::encode($pathItem) ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item text-muted"><?= Html::encode($pathItem) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm">
                <div class="card-header border-0 bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil me-2 text-danger"></i>
                        <?= Yii::t('app', 'Edit Category') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?= $this->render('_form', [
                        'model' => $model,
                        'parentOptions' => $parentOptions,
                    ]) ?>
                </div>
            </div>

            <!-- Children Info Card -->
            <?php if ($model->hasChildren()): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header border-0 bg-transparent">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-diagram-3 me-2 text-primary"></i>
                            <?= Yii::t('app', 'Subcategories ({count})', ['count' => count($model->children)]) ?>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($model->children as $child): ?>
                                <li class="list-group-item d-flex align-items-center <?= !$child->status ? 'opacity-50' : '' ?>">
                                    <span class="me-2" style="color: <?= Html::encode($child->color ?? '#dc2626') ?>;">
                                        <i class="bi <?= Html::encode($child->icon ?? 'bi-folder') ?>"></i>
                                    </span>
                                    <span class="flex-grow-1"><?= Html::encode($child->name) ?></span>
                                    <?php if ($child->hasChildren()): ?>
                                        <span class="badge bg-light text-muted">
                                            +<?= count($child->children) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?= Html::a(
                                        '<i class="bi bi-pencil"></i>',
                                        ['update', 'id' => $child->id],
                                        ['class' => 'btn btn-sm btn-link text-muted ms-2']
                                    ) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
