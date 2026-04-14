<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Index View for Income Categories
 *
 * Professional income categories listing with:
 * - Summary statistics cards (refreshed with PJAX)
 * - Search/filter panel (submit-based)
 * - Responsive data table with AJAX pagination
 * - Quick actions (create, export, delete)
 * - Bulk delete functionality
 *
 * @var yii\web\View $this
 * @var app\models\IncomeCategorySearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $stats Category statistics
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'Income Categories');

// Get query parameters for export
$searchParams = Yii::$app->request->queryParams['IncomeCategorySearch'] ?? [];

// Calculate totals
$totalRecords = $dataProvider->getTotalCount();

// PJAX container ID (without #)
$pjaxContainerId = 'income-category-pjax';
?>

<?php Pjax::begin([
    'id' => $pjaxContainerId,
    'timeout' => 10000,
    'enablePushState' => true,
    'clientOptions' => ['method' => 'GET'],
]); ?>

<div class="income-category-index">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-folder text-success me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Organize your income sources with custom categories') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export'),
                ['export', 'IncomeCategorySearch' => $searchParams],
                [
                    'class' => 'btn btn-outline-secondary',
                    'data-pjax' => '0',
                ]
            ) ?>
            <?= Html::button(
                '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Category'),
                [
                    'class' => 'btn btn-success btn-modal',
                    'data-url' => Url::to(['create']),
                    'data-icon' => '<i class="bi bi-folder-plus text-success me-2"></i>',
                    'data-title' => Yii::t('app', 'Add New Category'),
                    'data-target' => '#nemModal',
                ]
            ) ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Categories -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-folder"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Total Categories') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->formatter->asInteger($stats['total'] ?? $totalRecords) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Categories -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Active') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->formatter->asInteger($stats['active'] ?? 0) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inactive Categories -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Inactive') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->formatter->asInteger($stats['inactive'] ?? 0) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- With Records -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'With Records') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->formatter->asInteger($stats['withRecords'] ?? 0) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search/Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-funnel me-2"></i><?= Yii::t('app', 'Filter & Search') ?>
                </h5>
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#searchPanel" aria-expanded="true">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
        </div>
        <div class="collapse show" id="searchPanel">
            <div class="card-body">
                <?= $this->render('_search', ['model' => $searchModel]) ?>
            </div>
        </div>
    </div>

    <!-- Categories Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i><?= Yii::t('app', 'Category Records') ?>
                    <span class="badge bg-secondary ms-2"><?= $totalRecords ?></span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-danger d-none" id="bulk-delete-btn">
                        <i class="bi bi-trash me-1"></i><?= Yii::t('app', 'Delete Selected') ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($dataProvider->getTotalCount() > 0): ?>
                <div class="table-responsive">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'layout' => "{items}\n<div class='px-3 py-3 border-top'><div class='row align-items-center'><div class='col-sm-6'><div class='text-muted small'>{summary}</div></div><div class='col-sm-6'>{pager}</div></div></div>",
                        'tableOptions' => [
                            'class' => 'table table-hover align-middle mb-0',
                            'id' => 'income-category-table',
                        ],
                        'rowOptions' => function ($model) {
                            return ['data-id' => $model->id];
                        },
                        'pager' => [
                            'class' => \yii\bootstrap5\LinkPager::class,
                            'options' => ['class' => 'pagination pagination-sm justify-content-center justify-content-sm-end mb-0'],
                            'linkContainerOptions' => ['class' => 'page-item'],
                            'linkOptions' => ['class' => 'page-link'],
                            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
                            'maxButtonCount' => 5,
                        ],
                        'columns' => [
                            // Checkbox column
                            [
                                'class' => 'yii\grid\CheckboxColumn',
                                'headerOptions' => ['style' => 'width: 40px;', 'class' => 'text-center'],
                                'contentOptions' => ['class' => 'text-center'],
                                'checkboxOptions' => function ($model) {
                                    return ['value' => $model->id, 'class' => 'form-check-input'];
                                },
                            ],

                            // Category Name with Icon
                            [
                                'attribute' => 'name',
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'min-width: 250px;'],
                                'value' => function ($model) {
                                    $icon = $model->icon ?? 'bi-folder';
                                    $color = $model->color ?? '#16a34a';

                                    return '<div class="d-flex align-items-center gap-3">'
                                        . '<div class="category-icon" style="background-color: ' . Html::encode($color) . '20; color: ' . Html::encode($color) . ';">'
                                        . '<i class="bi ' . Html::encode($icon) . '"></i>'
                                        . '</div>'
                                        . '<div>'
                                        . '<div class="fw-semibold">' . Html::encode($model->name) . '</div>'
                                        . ($model->description
                                            ? '<small class="text-muted">' . Html::encode(\yii\helpers\StringHelper::truncate($model->description, 50)) . '</small>'
                                            : '')
                                        . '</div>'
                                        . '</div>';
                                },
                            ],

                            // Status Badge
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'width: 120px;', 'class' => 'text-center'],
                                'contentOptions' => ['class' => 'text-center'],
                                'value' => function ($model) {
                                    if ($model->status) {
                                        return '<span class="badge bg-success-subtle text-success">'
                                            . '<i class="bi bi-check-circle me-1"></i>'
                                            . Yii::t('app', 'Active')
                                            . '</span>';
                                    }
                                    return '<span class="badge bg-secondary-subtle text-secondary">'
                                        . '<i class="bi bi-x-circle me-1"></i>'
                                        . Yii::t('app', 'Inactive')
                                        . '</span>';
                                },
                            ],

                            // Income Count
                            [
                                'label' => Yii::t('app', 'Records'),
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'width: 100px;', 'class' => 'text-center'],
                                'contentOptions' => ['class' => 'text-center'],
                                'value' => function ($model) {
                                    $count = $model->getIncomes()->count();
                                    $class = $count > 0 ? 'text-success fw-medium' : 'text-muted';
                                    return '<span class="' . $class . '">' . Yii::$app->formatter->asInteger($count) . '</span>';
                                },
                            ],

                            // Created Date
                            [
                                'attribute' => 'created_at',
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'width: 140px;'],
                                'value' => function ($model) {
                                    return '<span class="text-muted small">'
                                        . '<i class="bi bi-calendar3 me-1"></i>'
                                        . Yii::$app->formatter->asDate($model->created_at, 'medium')
                                        . '</span>';
                                },
                            ],

                            // Action Buttons
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'header' => Yii::t('app', 'Actions'),
                                'headerOptions' => ['style' => 'width: 130px;', 'class' => 'text-center'],
                                'contentOptions' => ['class' => 'text-center', 'style' => 'white-space: nowrap;'],
                                'template' => '{view} {update} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model) {
                                        return Html::button(
                                            '<i class="bi bi-eye"></i>',
                                            [
                                                'class' => 'btn btn-sm btn-light btn-action me-1 btn-modal',
                                                'data-url' => Url::to(['view', 'id' => $model->id]),
                                                'data-icon' => '<i class="bi bi-folder text-success me-2"></i>',
                                                'data-title' => Yii::t('app', 'View Category'),
                                                'data-target' => '#nemModal',
                                                'title' => Yii::t('app', 'View'),
                                            ]
                                        );
                                    },
                                    'update' => function ($url, $model) {
                                        return Html::button(
                                            '<i class="bi bi-pencil"></i>',
                                            [
                                                'class' => 'btn btn-sm btn-light btn-action me-1 btn-modal',
                                                'data-url' => Url::to(['update', 'id' => $model->id]),
                                                'data-icon' => '<i class="bi bi-folder text-success me-2"></i>',
                                                'data-title' => Yii::t('app', 'Update Category'),
                                                'data-target' => '#nemModal',
                                                'title' => Yii::t('app', 'Edit'),
                                            ]
                                        );
                                    },
                                    'delete' => function ($url, $model) use ($pjaxContainerId) {
                                        return Html::button(
                                            '<i class="bi bi-trash"></i>',
                                            [
                                                'class' => 'btn btn-sm btn-light btn-action text-danger nemDeleteLink',
                                                'data-url' => Url::to(['delete', 'id' => $model->id]),
                                                'data-message' => Yii::t('app', 'Are you sure you want to delete "{name}"?', [
                                                    'name' => $model->name
                                                ]),
                                                'data-container' => $pjaxContainerId,
                                                'title' => Yii::t('app', 'Delete'),
                                            ]
                                        );
                                    },
                                ],
                            ],
                        ],
                    ]); ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-4">
                        <i class="bi bi-folder-plus text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2"><?= Yii::t('app', 'No categories found') ?></h5>
                    <p class="text-muted mb-4">
                        <?php if (!empty($searchModel->name) || $searchModel->status !== null && $searchModel->status !== ''): ?>
                            <?= Yii::t('app', 'No categories match your search criteria. Try adjusting your filters.') ?>
                        <?php else: ?>
                            <?= Yii::t('app', 'Create your first income category to start organizing your finances.') ?>
                        <?php endif; ?>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if (!empty($searchModel->name) || $searchModel->status !== null && $searchModel->status !== ''): ?>
                            <?= Html::a(
                                '<i class="bi bi-arrow-counterclockwise me-1"></i>' . Yii::t('app', 'Clear Filters'),
                                ['index'],
                                ['class' => 'btn btn-outline-secondary', 'data-pjax' => '0']
                            ) ?>
                        <?php endif; ?>
                        <?= Html::button(
                            '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Create Category'),
                            [
                                'class' => 'btn btn-success btn-modal',
                                'data-url' => Url::to(['create']),
                                'data-icon' => '<i class="bi bi-folder-plus text-success me-2"></i>',
                                'data-title' => Yii::t('app', 'Add New Category'),
                                'data-target' => '#nemModal',
                            ]
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php Pjax::end(); ?>

<?php
// Initialize page-specific components
$bulkDeleteUrl = Url::to(['bulk-delete']);

$js = <<<JS
(function() {
    'use strict';

    // Initialize grid checkbox handler
    NEM.GridCheckbox.init({
        tableSelector: '#income-category-table',
        bulkActionBtnSelector: '#bulk-delete-btn'
    });

    // Initialize bulk delete
    NEM.BulkDelete.init({
        btnSelector: '#bulk-delete-btn',
        tableSelector: '#income-category-table',
        url: '{$bulkDeleteUrl}',
        pjaxContainer: '{$pjaxContainerId}',
        confirmMessage: 'Are you sure you want to delete {count} selected category(s)?'
    });

})();
JS;

$this->registerJs($js);
