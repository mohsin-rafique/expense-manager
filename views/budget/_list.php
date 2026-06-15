<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Budget List Partial
 *
 * Renders the collection of budgets as responsive progress cards. Each card
 * shows the category, period, progress bar, spent/remaining amounts, an alert
 * badge, and edit/delete actions.
 *
 * @var yii\web\View $this
 * @var app\models\Budget[] $budgets
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$cur = Yii::$app->currency;
?>

<?php if (empty($budgets)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 72px; height: 72px;">
                    <i class="bi bi-wallet2" style="font-size: 2rem;"></i>
                </span>
            </div>
            <h5 class="mb-1"><?= Yii::t('app', 'No budgets yet') ?></h5>
            <p class="text-muted mb-3"><?= Yii::t('app', 'Create your first budget to start tracking spending against limits.') ?></p>
            <?= Html::button(
                '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Add Budget'),
                [
                    'class' => 'btn btn-primary btn-modal',
                    'data-url' => Url::to(['create']),
                    'data-title' => Yii::t('app', 'Add New Budget'),
                    'data-icon' => '<i class="bi bi-wallet2 text-primary me-2"></i>',
                    'data-target' => '#nemModal',
                ]
            ) ?>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($budgets as $budget): ?>
            <?php
            $color = $budget->getColor();              // success | warning | danger | info | primary
            $level = $budget->getStatusLevel();
            $spent = $budget->getSpentAmount();
            $remaining = $budget->getRemaining();
            $width = $budget->getProgressWidth();
            $isInactive = !$budget->status;
            $typeIcon = $budget->isExpense() ? 'bi-graph-down-arrow text-danger' : 'bi-graph-up-arrow text-success';
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 <?= $isInactive ? 'opacity-75' : '' ?>">
                    <div class="card-body">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?= $typeIcon ?>"></i>
                                <div>
                                    <div class="fw-semibold text-truncate" style="max-width: 180px;" title="<?= Html::encode($budget->getCategoryName()) ?>">
                                        <?= Html::encode($budget->getCategoryName()) ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?= Html::encode($budget->getPeriodTypeLabel()) ?> · <?= Html::encode($budget->getPeriodLabel()) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item btn-modal" href="#"
                                            data-url="<?= Url::to(['view', 'id' => $budget->id]) ?>"
                                            data-title="<?= Html::encode($budget->getCategoryName()) ?>"
                                            data-icon="<i class='bi bi-wallet2 text-primary me-2'></i>"
                                            data-target="#nemModal">
                                            <i class="bi bi-eye me-2"></i><?= Yii::t('app', 'View') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item btn-modal" href="#"
                                            data-url="<?= Url::to(['update', 'id' => $budget->id]) ?>"
                                            data-title="<?= Yii::t('app', 'Edit Budget') ?>"
                                            data-icon="<i class='bi bi-pencil text-primary me-2'></i>"
                                            data-target="#nemModal">
                                            <i class="bi bi-pencil me-2"></i><?= Yii::t('app', 'Edit') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item nemToggleStatus" href="#"
                                            data-url="<?= Url::to(['toggle-status', 'id' => $budget->id]) ?>"
                                            data-container="#budgets-pjax">
                                            <i class="bi bi-power me-2"></i><?= $budget->status ? Yii::t('app', 'Deactivate') : Yii::t('app', 'Activate') ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger nemDeleteLink" href="#"
                                            data-url="<?= Url::to(['delete', 'id' => $budget->id]) ?>"
                                            data-message="<?= Html::encode(Yii::t('app', 'Are you sure you want to delete the budget for "{name}"?', ['name' => $budget->getCategoryName()])) ?>"
                                            data-container="#budgets-pjax">
                                            <i class="bi bi-trash me-2"></i><?= Yii::t('app', 'Delete') ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Amounts -->
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <span class="h5 mb-0"><?= $cur->format($spent) ?></span>
                            <span class="text-muted small"><?= Yii::t('app', 'of') ?> <?= $budget->getFormattedAmount() ?></span>
                        </div>

                        <!-- Progress -->
                        <div class="progress mb-2" style="height: 8px;" role="progressbar" aria-valuenow="<?= (int) $width ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-<?= $color ?>" style="width: <?= $width ?>%;"></div>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>">
                                <?= Html::encode($budget->getStatusLabel()) ?> · <?= (int) round($budget->getPercentage()) ?>%
                            </span>
                            <span class="small <?= $remaining < 0 ? 'text-danger' : 'text-muted' ?>">
                                <?php if ($remaining < 0): ?>
                                    <?= $cur->format(abs($remaining)) ?> <?= Yii::t('app', 'over') ?>
                                <?php else: ?>
                                    <?= $cur->format($remaining) ?> <?= Yii::t('app', 'left') ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($budget->email_alerts): ?>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-envelope me-1"></i><?= Yii::t('app', 'Email alerts on') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
