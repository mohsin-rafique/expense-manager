<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Authentication Footer Partial View
 *
 * Simple footer for auth pages with copyright notice.
 *
 * @var yii\web\View $this The view object
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;
?>

<div class="text-center mt-4 text-muted">
    <small>
        &copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?>
    </small>
</div>
