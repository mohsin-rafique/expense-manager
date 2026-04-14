<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Index View for Incomes
 *
 * Professional income listing with statistics dashboard, advanced filtering,
 * and AJAX-powered CRUD operations using centralized NEM JavaScript system.
 *
 * @var yii\web\View $this
 * @var app\models\IncomeSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $statistics Summary statistics
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Income;

$this->title = Yii::t('app', 'Income');

// Prepare export URL with current filters
$exportParams = Yii::$app->request->queryParams['IncomeSearch'] ?? [];

// Format statistics using CurrencyFormatter component
$totalAmount = Yii::$app->currency->format($statistics['total_amount'] ?? 0);
$avgAmount = Yii::$app->currency->format($statistics['average'] ?? 0);
$recordCount = $statistics['count'] ?? 0;
?>

<div class="income-index">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-wallet2 text-success me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Track and manage your income sources') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export'),
                ['export', 'IncomeSearch' => $exportParams],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
            <?= Html::button(
                '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Income'),
                [
                    'class' => 'btn btn-success btn-modal',
                    'data-url' => Url::to(['create']),
                    'data-icon' => '<i class="bi bi-wallet2 text-success me-2"></i>',
                    'data-title' => Yii::t('app', 'Add New Income'),
                    'data-target' => '#nemModal',
                ]
            ) ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-label"><?= Yii::t('app', 'Total Income') ?></div>
                            <div class="stats-value text-success"><?= $totalAmount ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-label"><?= Yii::t('app', 'Transactions') ?></div>
                            <div class="stats-value"><?= Yii::$app->formatter->asInteger($recordCount) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stats-label"><?= Yii::t('app', 'Average') ?></div>
                            <div class="stats-value"><?= $avgAmount ?></div>
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

    <!-- Data Table Card -->
    <div class="card table-card shadow-sm">
        <div class="card-header bg-transparent border-0 py-3">
            <h5 class="card-title mb-0">
                <?= Yii::t('app', 'Income History') ?>
            </h5>
        </div>

        <div class="card-body p-0">
            <?php Pjax::begin([
                'id' => 'income-pjax',
                'timeout' => 10000,
                'enablePushState' => true,
                'clientOptions' => ['method' => 'GET'],
            ]); ?>

            <?php if ($dataProvider->getTotalCount() > 0): ?>
                <div class="table-responsive">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'layout' => "{items}\n<div class='d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 p-3 border-top'><div class='text-muted small'>{summary}</div><div>{pager}</div></div>",
                        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
                        'pager' => [
                            'class' => \yii\bootstrap5\LinkPager::class,
                            'options' => ['class' => 'pagination pagination-sm mb-0'],
                            'linkContainerOptions' => ['class' => 'page-item'],
                            'linkOptions' => ['class' => 'page-link'],
                            'maxButtonCount' => 5,
                        ],
                        'showFooter' => true,
                        'footerRowOptions' => ['class' => 'table-light fw-bold'],
                        'columns' => [
                            // Date
                            [
                                'attribute' => 'entry_date',
                                'label' => Yii::t('app', 'Date'),
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'width: 130px;'],
                                'value' => function ($model) {
                                    return '<span class="date-cell">'
                                        . '<i class="bi bi-calendar3 me-1"></i>'
                                        . Yii::$app->formatter->asDate($model->entry_date, 'medium')
                                        . '</span>';
                                },
                            ],

                            // Category
                            [
                                'attribute' => 'income_category_id',
                                'label' => Yii::t('app', 'Category'),
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'min-width: 150px;'],
                                'value' => function ($model) {
                                    $icon = $model->incomeCategory->icon ?? 'bi-folder';
                                    $color = $model->incomeCategory->color ?? '#16a34a';
                                    return '<span class="category-badge" style="background-color: ' . $color . '15; color: ' . $color . ';">'
                                        . '<i class="bi ' . Html::encode($icon) . '"></i>'
                                        . Html::encode($model->incomeCategory->name ?? 'N/A')
                                        . '</span>';
                                },
                                'footer' => Yii::t('app', 'Total'),
                            ],

                            // Reference
                            [
                                'attribute' => 'reference',
                                'headerOptions' => ['style' => 'width: 150px;'],
                                'contentOptions' => ['class' => 'text-truncate', 'style' => 'max-width: 150px;'],
                                'value' => function ($model) {
                                    return $model->reference ?: '—';
                                },
                            ],

                            // Description
                            [
                                'attribute' => 'description',
                                'format' => 'ntext',
                                'headerOptions' => ['style' => 'min-width: 200px;'],
                                'contentOptions' => ['class' => 'text-truncate', 'style' => 'max-width: 250px;'],
                                'value' => function ($model) {
                                    $desc = $model->description ?: '—';
                                    return \yii\helpers\StringHelper::truncate($desc, 50);
                                },
                            ],

                            // Attachment
                            [
                                'label' => '<i class="bi bi-paperclip"></i>',
                                'encodeLabel' => false,
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-center'],
                                'contentOptions' => ['class' => 'text-center'],
                                'value' => function ($model) {
                                    if ($model->hasAttachment()) {
                                        return '<i class="bi ' . $model->getFileIcon() . ' attachment-icon has-file" title="' . Html::encode($model->filename) . '"></i>';
                                    }
                                    return '<i class="bi bi-paperclip attachment-icon"></i>';
                                },
                            ],

                            // Amount
                            [
                                'attribute' => 'amount',
                                'label' => Yii::t('app', 'Amount'),
                                'format' => 'raw',
                                'headerOptions' => ['style' => 'width: 130px;', 'class' => 'text-end'],
                                'contentOptions' => ['class' => 'text-end amount-cell'],
                                'value' => function ($model) {
                                    return '<span class="amount-cell">' . $model->getFormattedAmount() . '</span>';
                                },
                                'footer' => Income::pageTotal($dataProvider->models, 'amount'),
                                'footerOptions' => ['class' => 'text-end text-success'],
                            ],

                            // Actions
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'header' => Yii::t('app', 'Actions'),
                                'headerOptions' => ['style' => 'width: 150px;', 'class' => 'text-center'],
                                'contentOptions' => ['class' => 'text-center'],
                                'template' => '{view} {update} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model) {
                                        return Html::button(
                                            '<i class="bi bi-eye"></i>',
                                            [
                                                'class' => 'btn btn-sm btn-light btn-action me-1 btn-modal',
                                                'data-url' => Url::to(['view', 'id' => $model->id]),
                                                'data-icon' => '<i class="bi bi-graph-up-arrow text-success me-2"></i>',
                                                'data-title' => Yii::t('app', 'View Income'),
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
                                                'data-icon' => '<i class="bi bi-graph-up-arrow text-success me-2"></i>',
                                                'data-title' => Yii::t('app', 'Update Income'),
                                                'title' => Yii::t('app', 'Edit'),
                                            ]
                                        );
                                    },
                                    'delete' => function ($url, $model) {
                                        return Html::button(
                                            '<i class="bi bi-trash"></i>',
                                            [
                                                'class' => 'btn btn-sm btn-light btn-action text-danger nemDeleteLink',
                                                'data-url' => Url::to(['delete', 'id' => $model->id]),
                                                'data-message' => Yii::t('app', 'Are you sure you want to delete "{name}"?', [
                                                    'name' => $model->incomeCategory->name . ' - ' . $model->getFormattedAmount()
                                                ]),
                                                'data-container' => '#income-pjax',
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
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-wallet2 text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h5><?= Yii::t('app', 'No income records found') ?></h5>
                    <p class="text-muted mb-4">
                        <?= Yii::t('app', 'Start tracking your income by adding your first record.') ?>
                    </p>
                    <?= Html::button(
                        '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Income'),
                        [
                            'class' => 'btn btn-success btn-modal',
                            'data-url' => Url::to(['create']),
                            'data-icon' => '<i class="bi bi-wallet2 text-success me-2"></i>',
                            'data-title' => Yii::t('app', 'Add New Income'),
                            'data-target' => '#nemModal',
                        ]
                    ) ?>
                </div>
            <?php endif; ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<?php
// Register income-specific configuration for NEM
$pjaxContainer = '#income-pjax';

$js = <<<JS
// Set default PJAX container for income module
if (typeof NEM !== 'undefined') {
    NEM.config.defaultPjaxContainer = '{$pjaxContainer}';
}
JS;

$this->registerJs($js);
