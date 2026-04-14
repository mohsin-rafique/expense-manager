<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Update View for Incomes
 *
 * @var yii\web\View $this
 * @var app\models\Income $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

$this->title = Yii::t('app', 'Update Income #{id}', ['id' => $model->id]);
?>

<div class="income-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]); ?>
</div>
