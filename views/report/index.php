<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Reports Dashboard
 *
 * Pick a report type and reporting period, then download a styled PDF. The
 * period controls (month / fiscal year / custom range / all-time) are shown
 * dynamically based on the selected period type.
 *
 * @var yii\web\View $this
 * @var array $reports Report type => label
 * @var array $periods Period type => label
 * @var array $fiscalYears Available fiscal years (from FiscalYearService)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Reports');

$pdfUrl = Url::to(['pdf']);

$reportMeta = [
    'summary' => ['icon' => 'bi-file-earmark-bar-graph', 'desc' => Yii::t('app', 'Income, expenses, net position and top categories.')],
    'category' => ['icon' => 'bi-pie-chart', 'desc' => Yii::t('app', 'Full spending and income breakdown by category.')],
    'income-expense' => ['icon' => 'bi-bar-chart-line', 'desc' => Yii::t('app', 'Income vs expense trend across the period.')],
    'budget' => ['icon' => 'bi-wallet2', 'desc' => Yii::t('app', 'Current progress of your active budgets.')],
];

$months = [];
for ($m = 1; $m <= 12; $m++) {
    $months[$m] = Yii::$app->formatter->asDate(sprintf('2020-%02d-01', $m), 'MMMM');
}
$years = range((int) date('Y'), (int) date('Y') - 6);
?>

<div class="reports-index">
    <div class="mb-4">
        <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0"><?= Yii::t('app', 'Generate and download professional PDF financial reports') ?></p>
    </div>

    <div class="row g-4">
        <!-- Report picker -->
        <div class="col-lg-7">
            <div class="row g-3" id="report-cards">
                <?php foreach ($reports as $key => $label): ?>
                    <div class="col-md-6">
                        <label class="card shadow-sm h-100 report-option mb-0" style="cursor: pointer;">
                            <div class="card-body d-flex align-items-start gap-3">
                                <input type="radio" name="report" value="<?= $key ?>" class="form-check-input mt-1" <?= $key === 'summary' ? 'checked' : '' ?>>
                                <div>
                                    <div class="fw-semibold">
                                        <i class="bi <?= $reportMeta[$key]['icon'] ?> me-1 text-primary"></i>
                                        <?= Html::encode($label) ?>
                                    </div>
                                    <small class="text-muted"><?= Html::encode($reportMeta[$key]['desc']) ?></small>
                                </div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Period + download -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="bi bi-calendar-range me-2 text-primary"></i><?= Yii::t('app', 'Period') ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?= Yii::t('app', 'Period') ?></label>
                        <?= Html::dropDownList('period', 'month', $periods, ['class' => 'form-select', 'id' => 'period-type']) ?>
                    </div>

                    <!-- Month -->
                    <div class="row g-2 period-pane" data-pane="month">
                        <div class="col-7">
                            <label class="form-label small"><?= Yii::t('app', 'Month') ?></label>
                            <?= Html::dropDownList('month', (int) date('n'), $months, ['class' => 'form-select', 'id' => 'sel-month']) ?>
                        </div>
                        <div class="col-5">
                            <label class="form-label small"><?= Yii::t('app', 'Year') ?></label>
                            <?= Html::dropDownList('year', (int) date('Y'), array_combine($years, $years), ['class' => 'form-select', 'id' => 'sel-year']) ?>
                        </div>
                    </div>

                    <!-- Fiscal year -->
                    <div class="period-pane d-none" data-pane="fiscal">
                        <label class="form-label small"><?= Yii::t('app', 'Fiscal Year') ?></label>
                        <?= Html::dropDownList('fy', null, array_map(fn ($f) => $f['label'], array_column($fiscalYears, null, 'label')) ?: [], ['class' => 'form-select', 'id' => 'sel-fy']) ?>
                    </div>

                    <!-- Custom range -->
                    <div class="row g-2 period-pane d-none" data-pane="custom">
                        <div class="col-6">
                            <label class="form-label small"><?= Yii::t('app', 'From') ?></label>
                            <?= Html::input('date', 'start', date('Y-m-01'), ['class' => 'form-control', 'id' => 'sel-start']) ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= Yii::t('app', 'To') ?></label>
                            <?= Html::input('date', 'end', date('Y-m-t'), ['class' => 'form-control', 'id' => 'sel-end']) ?>
                        </div>
                    </div>

                    <!-- Lifetime -->
                    <div class="period-pane d-none" data-pane="lifetime">
                        <p class="text-muted small mb-0"><?= Yii::t('app', 'Includes all transactions on record.') ?></p>
                    </div>

                    <a href="#" id="download-pdf" class="btn btn-primary w-100 mt-3" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i><?= Yii::t('app', 'Download PDF') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
(function() {
    'use strict';
    var periodType = document.getElementById('period-type');
    var panes = document.querySelectorAll('.period-pane');
    var btn = document.getElementById('download-pdf');
    var base = '{$pdfUrl}';

    function showPane() {
        panes.forEach(function(p) {
            p.classList.toggle('d-none', p.getAttribute('data-pane') !== periodType.value);
        });
    }
    periodType.addEventListener('change', showPane);
    showPane();

    function selectedReport() {
        var r = document.querySelector('input[name="report"]:checked');
        return r ? r.value : 'summary';
    }

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('report', selectedReport());
        var pt = periodType.value;
        params.set('period', pt);
        if (pt === 'month') {
            params.set('month', document.getElementById('sel-month').value);
            params.set('year', document.getElementById('sel-year').value);
        } else if (pt === 'fiscal') {
            var fy = document.getElementById('sel-fy');
            if (fy && fy.value) params.set('fy', fy.value);
        } else if (pt === 'custom') {
            params.set('start', document.getElementById('sel-start').value);
            params.set('end', document.getElementById('sel-end').value);
        }
        window.open(base + '?' + params.toString(), '_blank');
    });
})();
JS;
$this->registerJs($js);
?>
