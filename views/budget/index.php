<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
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
            <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'Set spending limits per category and get alerted before you overspend') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
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
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Active Budgets') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['active']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Approaching Limit') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['warning']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-octagon"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Over Budget') ?></div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asInteger($stats['over']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stats-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= Yii::t('app', 'Spent of Budgeted') ?></div>
                            <div class="h6 mb-0">
                                <?= $cur->format($stats['totalSpent']) ?>
                                <span class="text-muted small">/ <?= $cur->format($stats['totalBudget']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <?php $form = \yii\widgets\ActiveForm::begin([
                'action' => ['index'],
                'method' => 'get',
                'options' => ['data-pjax' => 1, 'class' => 'row g-2 align-items-end'],
            ]); ?>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Type') ?></label>
                    <?= Html::dropDownList('BudgetSearch[category_type]', $searchModel->category_type, \app\models\BudgetSearch::getCategoryTypeFilterOptions(), ['class' => 'form-select form-select-sm']) ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Period') ?></label>
                    <?= Html::dropDownList('BudgetSearch[period_type]', $searchModel->period_type, \app\models\BudgetSearch::getPeriodTypeFilterOptions(), ['class' => 'form-select form-select-sm']) ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Status') ?></label>
                    <?= Html::dropDownList('BudgetSearch[status]', $searchModel->status, \app\models\BudgetSearch::getStatusFilterOptions(), ['class' => 'form-select form-select-sm']) ?>
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <?= Html::submitButton('<i class="bi bi-funnel me-1"></i>' . Yii::t('app', 'Filter'), ['class' => 'btn btn-sm btn-outline-primary']) ?>
                    <?= Html::a('<i class="bi bi-arrow-counterclockwise"></i>', ['index'], ['class' => 'btn btn-sm btn-outline-secondary', 'data-pjax' => 1, 'title' => Yii::t('app', 'Reset')]) ?>
                </div>
            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Budget Cards -->
    <?= $this->render('_list', ['budgets' => $dataProvider->getModels()]) ?>

</div>

<?php Pjax::end(); ?>
