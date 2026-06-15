<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * View for BudgetOverviewWidget.
 *
 * @var yii\web\View $this
 * @var app\models\Budget[] $budgets Budgets to display (already filtered/sliced)
 * @var bool $hasBudgets Whether the user has any active expense budgets at all
 * @var string|null $containerClass
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$cur = Yii::$app->currency;
?>

<div class="card shadow-sm <?= Html::encode($containerClass ?? '') ?>">
    <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-wallet2 me-2 text-primary"></i>
            <?= Yii::t('app', 'Budget Overview') ?>
        </h5>
        <?= Html::a(Yii::t('app', 'Manage'), ['/budget'], ['class' => 'btn btn-sm btn-outline-primary', 'data-pjax' => '0']) ?>
    </div>
    <div class="card-body">
        <?php if (!$hasBudgets): ?>
            <div class="text-center py-4">
                <i class="bi bi-wallet2 text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mb-2 mt-2"><?= Yii::t('app', 'No budgets yet') ?></p>
                <?= Html::a('<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Create a budget'), ['/budget'], ['class' => 'btn btn-sm btn-primary', 'data-pjax' => '0']) ?>
            </div>
        <?php elseif (empty($budgets)): ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0 mt-2"><?= Yii::t('app', 'All budgets are on track.') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($budgets as $budget): ?>
                <?php
                $color = $budget->getColor();
                $width = $budget->getProgressWidth();
                $spent = $budget->getSpentAmount();
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-medium text-truncate" style="max-width: 60%;" title="<?= Html::encode($budget->getCategoryName()) ?>">
                            <?= Html::encode($budget->getCategoryName()) ?>
                        </span>
                        <span class="small text-<?= $color ?>">
                            <?= $cur->format($spent) ?> / <?= $budget->getFormattedAmount() ?>
                        </span>
                    </div>
                    <div class="progress" style="height: 6px;" role="progressbar" aria-valuenow="<?= (int) $width ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $width ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
