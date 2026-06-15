<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Index View for Budgets
 *
 * Displays per-category budgets as progress cards with alert states, plus
 * summary statistics. Uses the global AJAX modal (_modals.php) and the NEM
 * JavaScript system for create/edit/delete.
 *
 * @var yii\web\View $this
 * @var app\models\BudgetSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $stats Summary statistics
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

$this->title = Yii::t('app', 'Budgets');

$cur = Yii::$app->currency;
$usagePercent = $stats['totalBudget'] > 0
    ? min(100, round(($stats['totalSpent'] / $stats['totalBudget']) * 100))
    : 0;
?>

<?php Pjax::begin([
    'id' => 'budgets-pjax',
    'timeout' => 10000,
    'enablePushState' => true,
    'clientOptions' => ['method' => 'GET'],
]); ?>

<div class="budgets-index">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-wallet2 text-primary me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Set spending limits per category and get alerted before you overspend') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if (Yii::$app->workspace->can(\app\models\WorkspaceMember::CAN_MANAGE_DATA)): ?>
            <?= Html::button(
                '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Budget'),
                [
                    'class' => 'btn btn-primary btn-modal',
                    'data-url' => Url::to(['create']),
                    'data-title' => Yii::t('app', 'Add New Budget'),
                    'data-icon' => '<i class="bi bi-wallet2 text-primary me-2"></i>',
                    'data-target' => '#nemModal',
                    'id' => 'btn-create-budget',
                ]
            ) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Active Budgets Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Active Budgets') ?></p>
                            <h3 class="summary-value mb-0"><?= Yii::$app->formatter->asInteger($stats['active']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approaching Limit Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Approaching Limit') ?></p>
                            <h3 class="summary-value mb-0"><?= Yii::$app->formatter->asInteger($stats['warning']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Over Budget Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-octagon"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Over Budget') ?></p>
                            <h3 class="summary-value mb-0"><?= Yii::$app->formatter->asInteger($stats['over']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spent of Budgeted Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="summary-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="summary-label mb-1"><?= Yii::t('app', 'Spent of Budgeted') ?></p>
                            <h3 class="summary-value mb-0"><?= $cur->format($stats['totalSpent']) ?></h3>
                            <div class="summary-label mb-0">/ <?= $cur->format($stats['totalBudget']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-funnel me-2 text-primary"></i><?= Yii::t('app', 'Filter & Search') ?>
                </h5>
                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#budgetSearchPanel" aria-expanded="true">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
        </div>
        <div class="collapse show" id="budgetSearchPanel">
            <div class="card-body">
                <?php $form = \yii\widgets\ActiveForm::begin([
                    'action' => ['index'],
                    'method' => 'get',
                    'options' => ['data-pjax' => 1, 'class' => 'row g-3 align-items-end'],
                ]); ?>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Type') ?></label>
                        <?= Html::dropDownList('BudgetSearch[category_type]', $searchModel->category_type, \app\models\BudgetSearch::getCategoryTypeFilterOptions(), ['class' => 'form-select']) ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Period') ?></label>
                        <?= Html::dropDownList('BudgetSearch[period_type]', $searchModel->period_type, \app\models\BudgetSearch::getPeriodTypeFilterOptions(), ['class' => 'form-select']) ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Status') ?></label>
                        <?= Html::dropDownList('BudgetSearch[status]', $searchModel->status, \app\models\BudgetSearch::getStatusFilterOptions(), ['class' => 'form-select']) ?>
                    </div>
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <?= Html::submitButton('<i class="bi bi-funnel me-1"></i>' . Yii::t('app', 'Filter'), ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('<i class="bi bi-arrow-counterclockwise me-1"></i>' . Yii::t('app', 'Reset'), ['index'], ['class' => 'btn btn-outline-secondary', 'data-pjax' => 1, 'title' => Yii::t('app', 'Reset')]) ?>
                    </div>
                <?php \yii\widgets\ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <!-- Budget Cards -->
    <?= $this->render('_list', ['budgets' => $dataProvider->getModels()]) ?>

</div>

<?php Pjax::end(); ?>
