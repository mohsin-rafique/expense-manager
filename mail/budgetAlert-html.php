<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Budget alert email (HTML).
 *
 * @var yii\web\View $this
 * @var app\models\User $user
 * @var app\models\Budget $budget
 * @var float $spent
 * @var string $level   'warning' | 'over'
 * @var array $period   ['startDate', 'endDate', 'label']
 */

use yii\helpers\Html;

$cur = Yii::$app->currency;
$isOver = $level === \app\models\Budget::LEVEL_OVER;
$accent = $isOver ? '#dc2626' : '#d97706';
$percent = (int) round($budget->getPercentage());
$remaining = $budget->getRemaining();
$manageUrl = Yii::$app->urlManager->createAbsoluteUrl(['budget/index']);
?>
<div style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; max-width: 560px; margin: 0 auto;">
    <h2 style="color: <?= $accent ?>; margin-bottom: 4px;">
        <?= $isOver ? Yii::t('app', 'Budget exceeded') : Yii::t('app', 'Budget alert') ?>
    </h2>
    <p style="margin-top: 0; color: #6b7280;">
        <?= Html::encode($user->username) ?>,
        <?php if ($isOver): ?>
            <?= Yii::t('app', 'one of your budgets has gone over its limit.') ?>
        <?php else: ?>
            <?= Yii::t('app', 'one of your budgets is approaching its limit.') ?>
        <?php endif; ?>
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 8px 0; color: #6b7280;"><?= Yii::t('app', 'Category') ?></td>
            <td style="padding: 8px 0; text-align: right; font-weight: bold;"><?= Html::encode($budget->getCategoryName()) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #6b7280;"><?= Yii::t('app', 'Period') ?></td>
            <td style="padding: 8px 0; text-align: right;"><?= Html::encode($budget->getPeriodTypeLabel()) ?> · <?= Html::encode($period['label']) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #6b7280;"><?= Yii::t('app', 'Spent') ?></td>
            <td style="padding: 8px 0; text-align: right; color: <?= $accent ?>; font-weight: bold;"><?= Html::encode($cur->format($spent)) ?> (<?= $percent ?>%)</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #6b7280;"><?= Yii::t('app', 'Budget Amount') ?></td>
            <td style="padding: 8px 0; text-align: right;"><?= Html::encode($budget->getFormattedAmount()) ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #6b7280;"><?= $remaining < 0 ? Yii::t('app', 'Over by') : Yii::t('app', 'Remaining') ?></td>
            <td style="padding: 8px 0; text-align: right;"><?= Html::encode($cur->format(abs($remaining))) ?></td>
        </tr>
    </table>

    <p>
        <a href="<?= $manageUrl ?>" style="display: inline-block; background: <?= $accent ?>; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 6px;">
            <?= Yii::t('app', 'Review your budgets') ?>
        </a>
    </p>

    <p style="color: #9ca3af; font-size: 12px; margin-top: 24px;">
        <?= Yii::t('app', 'You are receiving this because email alerts are enabled for this budget in {appName}.', ['appName' => Yii::$app->name]) ?>
    </p>
</div>
