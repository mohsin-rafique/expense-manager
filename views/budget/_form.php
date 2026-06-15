<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Form Partial for Budgets
 *
 * Create/update form rendered inside the global AJAX modal. Lets the user pick
 * a category type (expense/income), category, period, amount, alert threshold,
 * and email-alert preference.
 *
 * @var yii\web\View $this
 * @var app\models\Budget $model
 * @var array $categoryOptions [id => name] for the initial category type
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;

$isNewRecord = $model->isNewRecord;
$currencyCode = Yii::$app->currency->currencyCode ?? '';
?>

<div class="budget-form">
    <?php $form = ActiveForm::begin([
        'id' => 'budget-form',
        'action' => $isNewRecord ? ['create'] : ['update', 'id' => $model->id],
        'options' => [
            'class' => 'data-form',
            'data-container' => '#budgets-pjax',
        ],
        'enableAjaxValidation' => false,
        'enableClientValidation' => false,
    ]); ?>

    <div class="row g-3">
        <!-- Category Type -->
        <div class="col-12">
            <label class="form-label"><?= Yii::t('app', 'Category Type') ?></label>
            <div class="btn-group w-100" role="group" aria-label="<?= Yii::t('app', 'Category Type') ?>">
                <?php foreach (\app\models\Budget::getCategoryTypeOptions() as $value => $label): ?>
                    <?php $checked = $model->category_type === $value; ?>
                    <input type="radio" class="btn-check" name="Budget[category_type]" id="bt-type-<?= $value ?>"
                        value="<?= $value ?>" autocomplete="off" <?= $checked ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="bt-type-<?= $value ?>"><?= Html::encode($label) ?></label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Category -->
        <div class="col-12">
            <?= $form->field($model, 'category_id')->dropDownList(
                $categoryOptions,
                [
                    'id' => 'budget-category-id',
                    'prompt' => Yii::t('app', '- Select Category -'),
                    'class' => 'form-select',
                ]
            )->label(Yii::t('app', 'Category') . ' <span class="text-danger">*</span>') ?>
        </div>

        <!-- Period -->
        <div class="col-md-6">
            <?= $form->field($model, 'period_type')->dropDownList(
                \app\models\Budget::getPeriodTypeOptions(),
                ['class' => 'form-select']
            )->label(Yii::t('app', 'Period') . ' <span class="text-danger">*</span>') ?>
        </div>

        <!-- Amount -->
        <div class="col-md-6">
            <?= $form->field($model, 'amount', [
                'template' => "{label}\n<div class=\"input-group\"><span class=\"input-group-text\">" . Html::encode($currencyCode) . "</span>{input}</div>\n{error}",
            ])->textInput([
                'class' => 'form-control amount-input',
                'id' => 'budget-amount',
                'inputmode' => 'decimal',
                'placeholder' => '0.00',
            ])->label(Yii::t('app', 'Budget Amount') . ' <span class="text-danger">*</span>') ?>
        </div>

        <!-- Alert Threshold -->
        <div class="col-12">
            <label class="form-label d-flex justify-content-between">
                <span><?= Yii::t('app', 'Alert Threshold') ?></span>
                <span class="badge bg-primary-subtle text-primary" id="threshold-value"><?= (int) $model->alert_threshold ?>%</span>
            </label>
            <?= Html::activeInput('range', $model, 'alert_threshold', [
                'class' => 'form-range',
                'id' => 'budget-threshold',
                'min' => 50,
                'max' => 100,
                'step' => 5,
            ]) ?>
            <small class="text-muted">
                <?= Yii::t('app', 'Warn me once spending reaches this percentage of the budget.') ?>
            </small>
        </div>

        <!-- Email Alerts -->
        <div class="col-12">
            <div class="form-check form-switch">
                <?= Html::activeCheckbox($model, 'email_alerts', [
                    'class' => 'form-check-input',
                    'id' => 'budget-email-alerts',
                    'label' => false,
                ]) ?>
                <label class="form-check-label" for="budget-email-alerts">
                    <?= Yii::t('app', 'Send me an email when this budget crosses its threshold') ?>
                </label>
            </div>
        </div>

        <!-- Note -->
        <div class="col-12">
            <?= $form->field($model, 'note')->textInput([
                'maxlength' => true,
                'placeholder' => Yii::t('app', 'Optional note for this budget'),
            ])->label(Yii::t('app', 'Note')) ?>
        </div>

        <!-- Status (existing records only) -->
        <?php if (!$isNewRecord): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <?= Html::activeCheckbox($model, 'status', [
                        'class' => 'form-check-input',
                        'id' => 'budget-status',
                        'label' => false,
                    ]) ?>
                    <label class="form-check-label" for="budget-status">
                        <?= Yii::t('app', 'Active') ?>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Form Actions -->
    <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-3">
        <?= Html::button(Yii::t('app', 'Cancel'), [
            'class' => 'btn btn-light',
            'data-bs-dismiss' => 'modal',
        ]) ?>
        <?= Html::submitButton(
            $isNewRecord
                ? '<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Create Budget')
                : '<i class="bi bi-check-lg me-1"></i>' . Yii::t('app', 'Save Changes'),
            ['class' => 'btn btn-primary']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$categoriesUrl = Url::to(['categories']);
$selectPrompt = Yii::t('app', '- Select Category -');
$currentCategoryId = (int) $model->category_id;
$js = <<<JS
(function() {
    'use strict';

    var form = document.getElementById('budget-form');
    if (!form) return;

    // Live threshold badge
    var range = document.getElementById('budget-threshold');
    var badge = document.getElementById('threshold-value');
    if (range && badge) {
        range.addEventListener('input', function() {
            badge.textContent = this.value + '%';
        });
    }

    // Reload category options when the category type changes
    var typeRadios = form.querySelectorAll('input[name="Budget[category_type]"]');
    var categorySelect = document.getElementById('budget-category-id');

    function loadCategories(type, selectedId) {
        if (!categorySelect) return;
        categorySelect.disabled = true;
        fetch('{$categoriesUrl}?type=' + encodeURIComponent(type), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            categorySelect.innerHTML = '';
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '{$selectPrompt}';
            categorySelect.appendChild(opt);
            Object.keys(data).forEach(function(id) {
                var o = document.createElement('option');
                o.value = id;
                o.textContent = data[id];
                if (parseInt(id, 10) === selectedId) o.selected = true;
                categorySelect.appendChild(o);
            });
            categorySelect.disabled = false;
        })
        .catch(function() { categorySelect.disabled = false; });
    }

    typeRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                loadCategories(this.value, 0);
            }
        });
    });
})();
JS;
$this->registerJs($js);
?>
