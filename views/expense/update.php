<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Update Expense View
 *
 * @var yii\web\View $this
 * @var app\models\Expense $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

$this->title = Yii::t('app', 'Update Expense #{id}', ['id' => $model->id]);
?>

<div class="expense-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]); ?>
</div>
