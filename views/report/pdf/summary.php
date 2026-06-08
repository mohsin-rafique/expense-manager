<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Financial Summary PDF report.
 *
 * @var yii\web\View $this
 * @var array $period
 * @var array $meta
 * @var array $summary  income/expense/net/savingsRate/...
 * @var array $expenseRows Category breakdown (expenses)
 * @var array $incomeRows  Category breakdown (income)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

$cur = Yii::$app->currency;
$netClass = $summary['net'] >= 0 ? 'pos' : 'neg';

$topExpenses = array_slice($expenseRows, 0, 10);
$expenseNote = count($expenseRows) > 10
    ? Yii::t('app', 'Showing top {n} of {total} categories.', ['n' => 10, 'total' => count($expenseRows)])
    : null;

echo $this->render('_header', [
    'title' => Yii::t('app', 'Financial Summary'),
    'period' => $period,
    'meta' => $meta,
]);
?>

<table class="metrics">
    <tr>
        <td>
            <div class="label"><?= Yii::t('app', 'Total Income') ?></div>
            <div class="value pos"><?= Html::encode($cur->format($summary['income'])) ?></div>
            <div class="label"><?= Yii::t('app', '{count} entries', ['count' => $summary['incomeCount']]) ?></div>
        </td>
        <td>
            <div class="label"><?= Yii::t('app', 'Total Expenses') ?></div>
            <div class="value neg"><?= Html::encode($cur->format($summary['expense'])) ?></div>
            <div class="label"><?= Yii::t('app', '{count} entries', ['count' => $summary['expenseCount']]) ?></div>
        </td>
        <td>
            <div class="label"><?= Yii::t('app', 'Net Balance') ?></div>
            <div class="value <?= $netClass ?>"><?= Html::encode($cur->format($summary['net'])) ?></div>
            <div class="label"><?= $summary['net'] >= 0 ? Yii::t('app', 'Surplus') : Yii::t('app', 'Deficit') ?></div>
        </td>
        <td>
            <div class="label"><?= Yii::t('app', 'Savings Rate') ?></div>
            <div class="value <?= $netClass ?>"><?= number_format($summary['savingsRate'], 1) ?>%</div>
            <div class="label"><?= Yii::t('app', 'of income') ?></div>
        </td>
    </tr>
</table>

<?= $this->render('_categoryTable', [
    'rows' => $topExpenses,
    'heading' => Yii::t('app', 'Expenses by Category'),
    'color' => '#dc3545',
    'limitNote' => $expenseNote,
]) ?>

<?= $this->render('_categoryTable', [
    'rows' => array_slice($incomeRows, 0, 10),
    'heading' => Yii::t('app', 'Income by Category'),
    'color' => '#16a34a',
    'limitNote' => null,
]) ?>
