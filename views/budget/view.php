<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Budget Detail View (modal)
 *
 * Read-only summary of a single budget: category, period window, progress, and
 * alert configuration.
 *
 * @var yii\web\View $this
 * @var app\models\Budget $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$cur = Yii::$app->currency;
$color = $model->getColor();
$spent = $model->getSpentAmount();
$remaining = $model->getRemaining();
$width = $model->getProgressWidth();
$period = $model->getCurrentPeriod();
$fmt = Yii::$app->formatter;
?>

<div class="budget-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="h5 mb-0"><?= Html::encode($model->getCategoryName()) ?></div>
            <div class="text-muted small">
                <span class="badge bg-<?= $model->isExpense() ? 'danger' : 'success' ?>-subtle text-<?= $model->isExpense() ? 'danger' : 'success' ?>">
                    <?= Html::encode(\app\models\Budget::getCategoryTypeOptions()[$model->category_type]) ?>
                </span>
                <?= Html::encode($model->getPeriodTypeLabel()) ?> · <?= Html::encode($model->getPeriodLabel()) ?>
            </div>
        </div>
        <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> fs-6">
            <?= Html::encode($model->getStatusLabel()) ?>
        </span>
    </div>

    <!-- Progress -->
    <div class="d-flex justify-content-between align-items-end mb-1">
        <span class="h4 mb-0"><?= $cur->format($spent) ?></span>
        <span class="text-muted"><?= Yii::t('app', 'of') ?> <?= $model->getFormattedAmount() ?></span>
    </div>
    <div class="progress mb-2" style="height: 12px;" role="progressbar" aria-valuenow="<?= (int) $width ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $width ?>%;"></div>
    </div>
    <div class="d-flex justify-content-between small mb-4">
        <span class="text-muted"><?= (int) round($model->getPercentage()) ?>% <?= Yii::t('app', 'used') ?></span>
        <span class="<?= $remaining < 0 ? 'text-danger' : 'text-muted' ?>">
            <?php if ($remaining < 0): ?>
                <?= $cur->format(abs($remaining)) ?> <?= Yii::t('app', 'over') ?>
            <?php else: ?>
                <?= $cur->format($remaining) ?> <?= Yii::t('app', 'left') ?>
            <?php endif; ?>
        </span>
    </div>

    <!-- Details -->
    <dl class="row mb-0 small">
        <dt class="col-5 text-muted fw-normal"><?= Yii::t('app', 'Period window') ?></dt>
        <dd class="col-7"><?= Html::encode($fmt->asDate($period['startDate'])) ?> - <?= Html::encode($fmt->asDate($period['endDate'])) ?></dd>

        <dt class="col-5 text-muted fw-normal"><?= Yii::t('app', 'Alert Threshold') ?></dt>
        <dd class="col-7"><?= (int) $model->alert_threshold ?>%</dd>

        <dt class="col-5 text-muted fw-normal"><?= Yii::t('app', 'Email Alerts') ?></dt>
        <dd class="col-7"><?= $model->email_alerts ? Yii::t('app', 'On') : Yii::t('app', 'Off') ?></dd>

        <dt class="col-5 text-muted fw-normal"><?= Yii::t('app', 'Status') ?></dt>
        <dd class="col-7"><?= $model->status ? Yii::t('app', 'Active') : Yii::t('app', 'Inactive') ?></dd>

        <?php if (!empty($model->note)): ?>
            <dt class="col-5 text-muted fw-normal"><?= Yii::t('app', 'Note') ?></dt>
            <dd class="col-7"><?= Html::encode($model->note) ?></dd>
        <?php endif; ?>
    </dl>

    <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-3">
        <?= Html::button(Yii::t('app', 'Close'), [
            'class' => 'btn btn-light',
            'data-bs-dismiss' => 'modal',
        ]) ?>
        <?= Html::button(
            '<i class="bi bi-pencil me-1"></i>' . Yii::t('app', 'Edit Budget'),
            [
                'class' => 'btn btn-primary btn-modal',
                'data-url' => Url::to(['update', 'id' => $model->id]),
                'data-title' => Yii::t('app', 'Edit Budget'),
                'data-icon' => '<i class="bi bi-pencil text-primary me-2"></i>',
                'data-target' => '#nemModal',
            ]
        ) ?>
    </div>
</div>
