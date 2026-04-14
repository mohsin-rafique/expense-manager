<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Create Income View
 *
 * @var yii\web\View $this
 * @var app\models\Income $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

$this->title = Yii::t('app', 'Add New Income');
?>

<div class="income-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]); ?>
</div>
