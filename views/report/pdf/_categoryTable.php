<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Category breakdown table (with percentage bars) for PDF reports.
 *
 * @var yii\web\View $this
 * @var array $rows Each: ['name','total','percent']
 * @var string $heading Section heading
 * @var string $color Hex bar colour
 * @var string|null $limitNote Optional note shown when the list is truncated
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

$cur = Yii::$app->currency;
$total = array_sum(array_map(static fn ($r) => (float) $r['total'], $rows));
?>

<h2 class="section"><?= Html::encode($heading) ?></h2>

<?php if (empty($rows)): ?>
    <div class="empty"><?= Yii::t('app', 'No data for this period.') ?></div>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 34%;"><?= Yii::t('app', 'Category') ?></th>
                <th style="width: 30%;"><?= Yii::t('app', 'Share') ?></th>
                <th class="num" style="width: 12%;">%</th>
                <th class="num" style="width: 24%;"><?= Yii::t('app', 'Amount') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr class="<?= $i % 2 ? 'even' : '' ?>">
                    <td><?= Html::encode($r['name']) ?></td>
                    <td>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= (float) $r['percent'] ?>%; background: <?= Html::encode($color) ?>;"></div>
                        </div>
                    </td>
                    <td class="num muted"><?= number_format((float) $r['percent'], 1) ?>%</td>
                    <td class="num"><?= Html::encode($cur->format((float) $r['total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="3"><?= Yii::t('app', 'Total') ?></td>
                <td class="num"><?= Html::encode($cur->format($total)) ?></td>
            </tr>
        </tbody>
    </table>
    <?php if (!empty($limitNote)): ?>
        <div class="muted" style="font-size: 8.5pt; margin-top: 4px;"><?= Html::encode($limitNote) ?></div>
    <?php endif; ?>
<?php endif; ?>
