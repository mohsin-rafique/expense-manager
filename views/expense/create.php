<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Create Expense View
 *
 * @var yii\web\View $this
 * @var app\models\Expense $model
 * @var bool $isDuplicate Whether the form is pre-filled from an existing expense
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

$isDuplicate = isset($isDuplicate) ? (bool) $isDuplicate : false;

$this->title = $isDuplicate
    ? Yii::t('app', 'Duplicate Expense')
    : Yii::t('app', 'Add New Expense');
?>

<div class="expense-create">
    <?= $this->render('_form', [
        'model' => $model,
        'isDuplicate' => $isDuplicate,
    ]); ?>
</div>
