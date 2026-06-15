<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Budget alert email (plain text).
 *
 * @var yii\web\View $this
 * @var app\models\User $user
 * @var app\models\Budget $budget
 * @var float $spent
 * @var string $level
 * @var array $period
 */

$cur = Yii::$app->currency;
$isOver = $level === \app\models\Budget::LEVEL_OVER;
$percent = (int) round($budget->getPercentage());
$remaining = $budget->getRemaining();
$manageUrl = Yii::$app->urlManager->createAbsoluteUrl(['budget/index']);

echo ($isOver ? Yii::t('app', 'Budget exceeded') : Yii::t('app', 'Budget alert')) . "\n\n";
echo $user->username . ",\n";
echo ($isOver
    ? Yii::t('app', 'one of your budgets has gone over its limit.')
    : Yii::t('app', 'one of your budgets is approaching its limit.')) . "\n\n";

echo Yii::t('app', 'Category') . ': ' . $budget->getCategoryName() . "\n";
echo Yii::t('app', 'Period') . ': ' . $budget->getPeriodTypeLabel() . ' - ' . $period['label'] . "\n";
echo Yii::t('app', 'Spent') . ': ' . $cur->format($spent) . " ({$percent}%)\n";
echo Yii::t('app', 'Budget Amount') . ': ' . $budget->getFormattedAmount() . "\n";
echo ($remaining < 0 ? Yii::t('app', 'Over by') : Yii::t('app', 'Remaining')) . ': ' . $cur->format(abs($remaining)) . "\n\n";

echo Yii::t('app', 'Review your budgets') . ': ' . $manageUrl . "\n";
