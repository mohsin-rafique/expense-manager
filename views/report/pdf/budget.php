<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Budget Status PDF report - current progress of active expense budgets.
 *
 * @var yii\web\View $this
 * @var array $period
 * @var array $meta
 * @var array $budgets Each: ['category','period','amount','spent','percent','status','level']
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

$cur = Yii::$app->currency;

$barColors = ['safe' => '#16a34a', 'warning' => '#d97706', 'over' => '#dc2626'];
$badgeClass = ['safe' => 'badge-safe', 'warning' => 'badge-warning', 'over' => 'badge-over'];

echo $this->render('_header', [
    'title' => Yii::t('app', 'Budget Status'),
    'period' => $period,
    'meta' => $meta,
]);
?>

<p class="muted" style="font-size: 9pt;">
    <?= Yii::t('app', 'Budgets are evaluated against their own current period (monthly, yearly, or fiscal), independent of the report period above.') ?>
</p>

<?php if (empty($budgets)): ?>
    <div class="empty"><?= Yii::t('app', 'No active budgets.') ?></div>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 24%;"><?= Yii::t('app', 'Category') ?></th>
                <th style="width: 20%;"><?= Yii::t('app', 'Period') ?></th>
                <th style="width: 18%;"><?= Yii::t('app', 'Progress') ?></th>
                <th class="num"><?= Yii::t('app', 'Spent') ?></th>
                <th class="num"><?= Yii::t('app', 'Budget') ?></th>
                <th><?= Yii::t('app', 'Status') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($budgets as $i => $b): ?>
                <?php $width = min(100, max(0, (float) $b['percent'])); ?>
                <tr class="<?= $i % 2 ? 'even' : '' ?>">
                    <td><?= Html::encode($b['category']) ?></td>
                    <td class="muted"><?= Html::encode($b['period']) ?></td>
                    <td>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%; background: <?= $barColors[$b['level']] ?? '#0d6efd' ?>;"></div>
                        </div>
                        <span class="muted" style="font-size: 8pt;"><?= (int) round($b['percent']) ?>%</span>
                    </td>
                    <td class="num"><?= Html::encode($cur->format($b['spent'])) ?></td>
                    <td class="num"><?= Html::encode($cur->format($b['amount'])) ?></td>
                    <td><span class="badge <?= $badgeClass[$b['level']] ?? '' ?>"><?= Html::encode($b['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
