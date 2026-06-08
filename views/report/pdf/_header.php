<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Shared PDF report header + stylesheet.
 *
 * Emitted at the top of every PDF report template. Defines the document CSS
 * (mPDF-compatible) and renders the branded title block.
 *
 * @var yii\web\View $this
 * @var string $title Report title
 * @var array $period ['start','end','label','type']
 * @var array $meta ['appName','company','user','generatedAt']
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
?>
<style>
    body { font-family: sans-serif; color: #1f2937; font-size: 10pt; }
    h1.report-title { font-size: 18pt; color: #0d6efd; margin: 0 0 2px 0; }
    .report-head { border-bottom: 2px solid #0d6efd; padding-bottom: 6px; margin-bottom: 12px; }
    .report-head .company { font-size: 12pt; font-weight: bold; color: #111827; }
    .report-head .meta { font-size: 9pt; color: #6b7280; }
    .report-period { background: #eef4ff; color: #0d6efd; padding: 4px 8px; border-radius: 4px; font-size: 9.5pt; font-weight: bold; }

    h2.section { font-size: 12pt; color: #111827; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin: 16px 0 8px 0; }

    table { border-collapse: collapse; width: 100%; }
    table.metrics td { width: 25%; padding: 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    table.metrics .label { font-size: 8.5pt; color: #6b7280; }
    table.metrics .value { font-size: 13pt; font-weight: bold; }

    table.data { font-size: 9.5pt; }
    table.data th { background: #0d6efd; color: #fff; text-align: left; padding: 5px 7px; font-size: 9pt; }
    table.data td { padding: 5px 7px; border-bottom: 0.5px solid #e5e7eb; }
    table.data tr.even td { background: #f8fafc; }
    table.data td.num, table.data th.num { text-align: right; }
    table.data tr.total td { font-weight: bold; border-top: 1.5px solid #0d6efd; background: #eef4ff; }

    .pos { color: #16a34a; }
    .neg { color: #dc2626; }
    .muted { color: #6b7280; }

    .bar-track { background: #eef2f7; border-radius: 3px; height: 9px; width: 100%; }
    .bar-fill { height: 9px; border-radius: 3px; }
    .badge { padding: 1px 6px; border-radius: 3px; font-size: 8pt; font-weight: bold; }
    .badge-safe { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-over { background: #fee2e2; color: #991b1b; }
    .empty { color: #9ca3af; font-style: italic; padding: 10px 0; }
</style>

<div class="report-head">
    <table width="100%">
        <tr>
            <td style="vertical-align: top;">
                <div class="company"><?= Html::encode($meta['company']) ?></div>
                <h1 class="report-title"><?= Html::encode($title) ?></h1>
                <div class="meta">
                    <?= Yii::t('app', 'Prepared for {user}', ['user' => Html::encode($meta['user'])]) ?>
                    · <?= Html::encode($meta['generatedAt']) ?>
                </div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <span class="report-period"><?= Html::encode($period['label']) ?></span>
            </td>
        </tr>
    </table>
</div>
