<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Global Modals Partial View
 *
 * Renders global modal dialogs used throughout the application:
 * - nemModal: General purpose AJAX content modal
 * - deleteModal: Delete confirmation dialog
 *
 * @var yii\web\View $this The view object
 *
 * @see views/layouts/main.php Parent layout
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
?>

<!-- ============================================================== -->
<!-- Global AJAX Modal                                              -->
<!-- ============================================================== -->
<?php
Modal::begin([
    'id' => 'nemModal',
    'title' => '<span class="nem-modal-icon"></span><span class="nem-modal-title"></span>',
    'size' => Modal::SIZE_LARGE,
    'options' => [
        'tabindex' => false,
        'class' => 'fade',
    ],
    'headerOptions' => ['class' => 'border-bottom'],
]);
?>
<div class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden"><?= Yii::t('app', 'Loading...') ?></span>
    </div>
</div>
<?php Modal::end(); ?>

<!-- ============================================================== -->
<!-- Delete Confirmation Modal                                      -->
<!-- ============================================================== -->
<?php
Modal::begin([
    'id' => 'deleteModal',
    'title' => '<i class="bi bi-exclamation-triangle text-danger me-2"></i>' . Yii::t('app', 'Confirm Delete'),
    'size' => Modal::SIZE_SMALL,
    'options' => ['class' => 'fade'],
    'headerOptions' => ['class' => 'border-bottom'],
]);
?>
<div class="text-center py-3">
    <div class="mb-3">
        <span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 64px; height: 64px;">
            <i class="bi bi-trash" style="font-size: 1.5rem;"></i>
        </span>
    </div>
    <p class="delete-message mb-0"><?= Yii::t('app', 'Are you sure you want to delete this item?') ?></p>
    <p class="text-muted small"><?= Yii::t('app', 'This action cannot be undone.') ?></p>
</div>
<div class="d-flex gap-2 justify-content-center pb-3">
    <?= Html::button(Yii::t('app', 'Cancel'), [
        'class' => 'btn btn-secondary',
        'data-bs-dismiss' => 'modal',
    ]) ?>
    <?= Html::button('<i class="bi bi-trash me-1"></i>' . Yii::t('app', 'Delete'), [
        'class' => 'btn btn-danger delete-record',
    ]) ?>
</div>
<?php Modal::end(); ?>
