<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Income vs Expense PDF report - per-period trend within the date range.
 *
 * @var yii\web\View $this
 * @var array $period
 * @var array $meta
 * @var array $summary
 * @var array $trend Each: ['label','income','expense','net']
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

$cur = Yii::$app->currency;

echo $this->render('_header', [
    'title' => Yii::t('app', 'Income vs Expense'),
    'period' => $period,
    'meta' => $meta,
]);
?>

<table class="metrics">
    <tr>
        <td>
            <div class="label"><?= Yii::t('app', 'Total Income') ?></div>
            <div class="value pos"><?= Html::encode($cur->format($summary['income'])) ?></div>
        </td>
        <td>
            <div class="label"><?= Yii::t('app', 'Total Expenses') ?></div>
            <div class="value neg"><?= Html::encode($cur->format($summary['expense'])) ?></div>
        </td>
        <td>
            <div class="label"><?= Yii::t('app', 'Net Balance') ?></div>
            <div class="value <?= $summary['net'] >= 0 ? 'pos' : 'neg' ?>"><?= Html::encode($cur->format($summary['net'])) ?></div>
        </td>
        <td>
            <div class="label"><?= Yii::t('app', 'Savings Rate') ?></div>
            <div class="value <?= $summary['net'] >= 0 ? 'pos' : 'neg' ?>"><?= number_format($summary['savingsRate'], 1) ?>%</div>
        </td>
    </tr>
</table>

<h2 class="section"><?= Yii::t('app', 'Period Breakdown') ?></h2>

<?php if (empty($trend)): ?>
    <div class="empty"><?= Yii::t('app', 'No data for this period.') ?></div>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 28%;"><?= Yii::t('app', 'Period') ?></th>
                <th class="num"><?= Yii::t('app', 'Income') ?></th>
                <th class="num"><?= Yii::t('app', 'Expenses') ?></th>
                <th class="num"><?= Yii::t('app', 'Net') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trend as $i => $row): ?>
                <tr class="<?= $i % 2 ? 'even' : '' ?>">
                    <td><?= Html::encode($row['label']) ?></td>
                    <td class="num pos"><?= Html::encode($cur->format($row['income'])) ?></td>
                    <td class="num neg"><?= Html::encode($cur->format($row['expense'])) ?></td>
                    <td class="num <?= $row['net'] >= 0 ? 'pos' : 'neg' ?>"><?= Html::encode($cur->format($row['net'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td><?= Yii::t('app', 'Total') ?></td>
                <td class="num"><?= Html::encode($cur->format($summary['income'])) ?></td>
                <td class="num"><?= Html::encode($cur->format($summary['expense'])) ?></td>
                <td class="num"><?= Html::encode($cur->format($summary['net'])) ?></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
