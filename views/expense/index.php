<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Expenses Index View
 *
 * Modern expenses interface with:
 * - Summary statistics cards (refreshed with PJAX)
 * - Advanced search/filter panel
 * - Responsive data table with AJAX pagination
 * - Quick actions (create, export, delete)
 *
 * @var yii\web\View $this
 * @var app\models\ExpenseSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $summary
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use app\components\CustomGridView;

$this->title = Yii::t('app', 'Expenses');

// Get query parameters for export — fall back to searchModel's active values
// so the export URL reflects the default date range even when no query params are set.
$expensesSearchParams = Yii::$app->request->queryParams['ExpenseSearch'] ?? [
    'start_date' => $searchModel->start_date,
    'end_date'   => $searchModel->end_date,
];

// Calculate page total
$pageTotal = array_sum(array_column($dataProvider->models, 'amount'));
$totalRecords = $dataProvider->getTotalCount();

// PJAX container ID (without #)
$pjaxContainerId = 'expenses-pjax';
?>

<?php Pjax::begin([
    'id' => $pjaxContainerId,
    'timeout' => 10000,
    'enablePushState' => true,
    'clientOptions' => ['method' => 'GET'],
]); ?>

<div class="expense-index">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-wallet2 text-danger me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Track and manage your expenses') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export'),
                ['export', 'ExpenseSearch' => $expensesSearchParams],
                [
                    'class' => 'btn btn-outline-secondary',
                    'data-pjax' => '0',
                ]
            ) ?>
            <?= Html::button(
                '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Expense'),
                [
                    'class' => 'btn btn-danger btn-modal',
                    'data-url' => Url::to(['create']),
                    'data-icon' => '<i class="bi bi-graph-down-arrow text-danger me-2"></i>',
                    'data-title' => Yii::t('app', 'Add New Expense'),
                    'data-target' => '#nemModal',
                ]
            ) ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Expenses Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Total Expenses') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->currency->format($summary['total'] ?? 0) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Count Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Transactions') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->formatter->asInteger($summary['count'] ?? 0) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Average') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->currency->format($summary['average'] ?? 0) ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Total Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Page Total') ?></p>
                            <h3 class="summary-value mb-0">
                                <?= Yii::$app->currency->format($pageTotal) ?>
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

    <!-- Expenses Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i><?= Yii::t('app', 'Expense Records') ?>
                    <span class="badge bg-secondary ms-2"><?= $totalRecords ?></span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-danger d-none" id="bulk-delete-btn" onclick="deleteSelected()">
                        <i class="bi bi-trash me-1"></i><?= Yii::t('app', 'Delete Selected') ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <?= CustomGridView::widget([
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
                    'layout' => '
                        <div class="px-3 py-3 border-bottom bg-light">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">{search}</div>
                                <div class="col-md-6 text-md-end">{numberofentries}</div>
                            </div>
                        </div>
                        {items}
                        <div class="px-3 py-3 border-top">
                            <div class="row g-3 align-items-center">
                                <div class="col-sm-6">
                                    <div class="text-muted small">{summary}</div>
                                </div>
                                <div class="col-sm-6">{pager}</div>
                            </div>
                        </div>',
                    'tableOptions' => [
                        'class' => 'table table-hover table-striped align-middle mb-0',
                        'id' => 'expenses-table',
                    ],
                    'filterRowOptions' => ['style' => 'display:none;'],
                    'showFooter' => true,
                    'footerRowOptions' => ['class' => 'table-light fw-bold'],
                    'pager' => [
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
                        ],

                        // Date - Fixed width, no wrap
                        [
                            'attribute' => 'expense_date',
                            'headerOptions' => ['style' => 'width: 110px; white-space: nowrap;'],
                            'contentOptions' => ['style' => 'white-space: nowrap;'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                return '<span class="date-cell">'
                                    . '<i class="bi bi-calendar3 me-1"></i>'
                                    . Yii::$app->formatter->asDate($model->expense_date, 'medium')
                                    . '</span>';
                            },
                        ],

                        // Category - Flexible
                        [
                            'attribute' => 'expense_category_id',
                            'headerOptions' => ['style' => 'width: 100px;'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                $icon = $model->expenseCategory->icon ?? 'bi-tag';
                                return '<span class="badge bg-light text-dark">
                                    <i class="bi ' . $icon . ' me-1"></i>' .
                                    Html::encode($model->expenseCategory->name ?? 'N/A') .
                                    '</span>';
                            },
                            'footer' => '<strong>' . Yii::t('app', 'Page Total') . '</strong>',
                        ],

                        // Payment Method - Compact
                        [
                            'attribute' => 'payment_method',
                            'headerOptions' => ['style' => 'width: 100px;'],
                            'contentOptions' => ['style' => 'white-space: nowrap;'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                if (empty($model->payment_method)) {
                                    return '<span class="text-muted">—</span>';
                                }
                                $badges = [
                                    'Cash' => ['class' => 'bg-success bg-opacity-10 text-success', 'icon' => 'bi-cash'],
                                    'Card' => ['class' => 'bg-primary bg-opacity-10 text-primary', 'icon' => 'bi-credit-card'],
                                    'Bank' => ['class' => 'bg-info bg-opacity-10 text-info', 'icon' => 'bi-bank'],
                                ];
                                $badge = $badges[$model->payment_method] ?? ['class' => 'bg-secondary bg-opacity-10 text-secondary', 'icon' => 'bi-question'];
                                return '<span class="badge ' . $badge['class'] . '">
                                    <i class="bi ' . $badge['icon'] . ' me-1"></i>' .
                                    Html::encode($model->payment_method) .
                                    '</span>';
                            },
                        ],

                        // Reference - Truncated
                        [
                            'attribute' => 'reference',
                            'headerOptions' => ['style' => 'width: 120px;'],
                            'contentOptions' => ['class' => 'text-truncate', 'style' => 'max-width: 120px;'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                if (empty($model->reference)) {
                                    return '<span class="text-muted">—</span>';
                                }
                                return '<span title="' . Html::encode($model->reference) . '">' .
                                    Html::encode($model->reference) . '</span>';
                            },
                        ],

                        // Description - Flexible, takes remaining space
                        [
                            'attribute' => 'description',
                            'headerOptions' => ['style' => 'min-width: 150px;'],
                            'contentOptions' => ['class' => 'text-truncate', 'style' => 'max-width: 200px;'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                if (empty($model->description)) {
                                    return '<span class="text-muted">—</span>';
                                }
                                $desc = \yii\helpers\StringHelper::truncate($model->description, 40);
                                return '<span title="' . Html::encode($model->description) . '">' .
                                    Html::encode($desc) . '</span>';
                            },
                        ],

                        // Attachment indicator - Minimal
                        [
                            'attribute' => 'filename',
                            'label' => '<i class="bi bi-paperclip"></i>',
                            'encodeLabel' => false,
                            'headerOptions' => ['style' => 'width: 40px;', 'class' => 'text-center'],
                            'contentOptions' => ['class' => 'text-center'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                if (empty($model->filename)) {
                                    return '';
                                }
                                $icon = $model->isPdfFile() ? 'bi-file-pdf text-danger' : 'bi-image text-primary';
                                return '<i class="bi ' . $icon . '" title="' . Html::encode($model->filename) . '"></i>';
                            },
                        ],

                        // Amount - Fixed width, no wrap
                        [
                            'attribute' => 'amount',
                            'headerOptions' => ['style' => 'width: 120px; white-space: nowrap;', 'class' => 'text-end'],
                            'contentOptions' => ['class' => 'text-end fw-medium text-danger', 'style' => 'white-space: nowrap;'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                return '<span class="amount-cell">' . $model->getFormattedAmount() . '</span>';
                            },
                            'footer' => '<span class="text-danger">' .
                                Yii::$app->currency->format(array_sum(array_column($dataProvider->models, 'amount'))) .
                                '</span>',
                            'footerOptions' => ['class' => 'text-end', 'style' => 'white-space: nowrap;'],
                        ],

                        // Actions - Fixed width, no wrap
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => Yii::t('app', 'Actions'),
                            'headerOptions' => ['style' => 'width: 120px; white-space: nowrap;', 'class' => 'text-center'],
                            'contentOptions' => ['class' => 'text-center', 'style' => 'white-space: nowrap;'],
                            'template' => '{view} {update} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::button(
                                        '<i class="bi bi-eye"></i>',
                                        [
                                            'class' => 'btn btn-sm btn-light btn-action me-1 btn-modal',
                                            'data-url' => Url::to(['view', 'id' => $model->id]),
                                            'data-icon' => '<i class="bi bi-graph-down-arrow text-danger me-2"></i>',
                                            'data-title' => Yii::t('app', 'View Expense'),
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
                                            'data-icon' => '<i class="bi bi-graph-down-arrow text-danger me-2"></i>',
                                            'data-title' => Yii::t('app', 'Update Expense'),
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
                                                'name' => $model->expenseCategory->name . ' - ' . $model->getFormattedAmount()
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
        </div>
    </div>
</div>

<?php Pjax::end(); ?>

<?php
// Initialize page-specific components
$js = <<<JS
$(function() {
    // Initialize grid checkbox handler
    NEM.GridCheckbox.init({
        tableSelector: '#expenses-table',
        bulkActionBtnSelector: '#bulk-delete-btn'
    });

    // Initialize bulk delete (optional - when you implement the endpoint)
    // NEM.BulkDelete.init({
    //     btnSelector: '#bulk-delete-btn',
    //     tableSelector: '#expenses-table',
    //     url: '/expenses/bulk-delete',
    //     pjaxContainer: 'expenses-pjax'
    // });
});
JS;

$this->registerJs($js);
