<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * View Partial for Income Details
 *
 * Displays comprehensive details of a single income record
 * including category, amount, dates, and attachment preview.
 *
 * @var yii\web\View $this
 * @var app\models\Income $model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$category = $model->incomeCategory;
$icon = $category->icon ?? 'bi-folder';
$color = $category->color ?? '#16a34a';
?>

<div class="income-view">
    <!-- Header with Amount -->
    <div class="text-center pb-4 mb-4 border-bottom">
        <div class="category-icon-large mx-auto mb-3"
            style="width: 72px; height: 72px; display: flex; align-items: center; justify-content: center;
                    border-radius: 16px; font-size: 2rem;
                    background-color: <?= Html::encode($color) ?>15; color: <?= Html::encode($color) ?>;">
            <i class="bi <?= Html::encode($icon) ?>"></i>
        </div>

        <h3 class="text-success mb-2"><?= $model->getFormattedAmount() ?></h3>

        <span class="badge rounded-pill"
            style="background-color: <?= Html::encode($color) ?>15; color: <?= Html::encode($color) ?>; padding: 0.5rem 1rem;">
            <i class="bi <?= Html::encode($icon) ?> me-1"></i>
            <?= Html::encode($category->name ?? 'Uncategorized') ?>
        </span>
    </div>

    <!-- Details Grid -->
    <div class="row g-3 mb-4">
        <!-- Date -->
        <div class="col-6">
            <div class="detail-item p-3 bg-light rounded-3">
                <div class="text-muted small mb-1">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= Yii::t('app', 'Date') ?>
                </div>
                <div class="fw-semibold">
                    <?= $model->getFormattedDate('long') ?>
                </div>
            </div>
        </div>

        <!-- Reference -->
        <div class="col-6">
            <div class="detail-item p-3 bg-light rounded-3">
                <div class="text-muted small mb-1">
                    <i class="bi bi-hash me-1"></i>
                    <?= Yii::t('app', 'Reference') ?>
                </div>
                <div class="fw-semibold">
                    <?= $model->reference ? Html::encode($model->reference) : '<span class="text-muted">-</span>' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if ($model->description): ?>
        <div class="mb-4">
            <label class="text-muted small d-block mb-2">
                <i class="bi bi-card-text me-1"></i>
                <?= Yii::t('app', 'Description') ?>
            </label>
            <div class="p-3 bg-light rounded-3">
                <?= nl2br(Html::encode($model->description)) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Attachment -->
    <?php if ($model->hasAttachment()): ?>
        <div class="mb-4">
            <label class="text-muted small d-block mb-2">
                <i class="bi bi-paperclip me-1"></i>
                <?= Yii::t('app', 'Attachment') ?>
            </label>
            <div class="attachment-card p-3 bg-light rounded-3">
                <div class="d-flex align-items-center">
                    <div class="attachment-icon me-3">
                        <i class="bi <?= $model->getFileIcon() ?>" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium text-truncate" style="max-width: 200px;" title="<?= Html::encode($model->filename) ?>">
                            <?= Html::encode($model->filename) ?>
                        </div>
                        <small class="text-muted"><?= $model->getFileSizeFormatted() ?></small>
                    </div>
                    <div class="ms-2">
                        <?php if ($model->isImageAttachment()): ?>
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#imagePreviewModal"
                                title="<?= Yii::t('app', 'Preview') ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                        <?php endif; ?>
                        <a href="<?= $model->getFileUrl() ?>"
                            class="btn btn-sm btn-outline-primary"
                            target="_blank"
                            download
                            title="<?= Yii::t('app', 'Download') ?>">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>

                <!-- Image Preview (inline for images) -->
                <?php if ($model->isImageAttachment()): ?>
                    <div class="mt-3 text-center">
                        <img src="<?= $model->getFileUrl() ?>"
                            alt="<?= Html::encode($model->filename) ?>"
                            class="img-fluid rounded shadow-sm"
                            style="max-height: 200px; cursor: pointer;"
                            data-bs-toggle="modal"
                            data-bs-target="#imagePreviewModal">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <?php if ($model->isImageAttachment()): ?>
            <div class="modal fade" id="imagePreviewModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h6 class="modal-title"><?= Html::encode($model->filename) ?></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img src="<?= $model->getFileUrl() ?>"
                                alt="<?= Html::encode($model->filename) ?>"
                                class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Meta Information -->
    <div class="row g-3 text-muted small mb-4">
        <div class="col-6">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock"></i>
                <div>
                    <div><?= Yii::t('app', 'Created') ?></div>
                    <div class="text-dark"><?= Yii::$app->formatter->asDatetime($model->created_at, 'medium') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history"></i>
                <div>
                    <div><?= Yii::t('app', 'Updated') ?></div>
                    <div class="text-dark"><?= Yii::$app->formatter->asDatetime($model->updated_at, 'medium') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2 justify-content-between border-top pt-4">
        <div>
            <?= Html::button(
                '<i class="bi bi-printer me-1"></i>' . Yii::t('app', 'Print'),
                [
                    'class' => 'btn btn-outline-secondary btn-sm',
                    'onclick' => 'window.print()',
                ]
            ) ?>
        </div>
        <div class="d-flex gap-2">
            <?= Html::button(
                Yii::t('app', 'Close'),
                [
                    'class' => 'btn btn-light',
                    'data-bs-dismiss' => 'modal',
                ]
            ) ?>
            <?= Html::button(
                '<i class="bi bi-pencil me-1"></i>' . Yii::t('app', 'Edit'),
                [
                    'class' => 'btn btn-primary btn-modal',
                    'data-url' => Url::to(['update', 'id' => $model->id]),
                    'data-icon' => '<i class="bi bi-graph-up-arrow text-success me-2"></i>',
                    'data-title' => Yii::t('app', 'Update Income'),
                ]
            ) ?>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
    .income-view .detail-item {
        height: 100%;
    }
    .income-view .attachment-card {
        transition: all 0.2s ease;
    }
    .income-view .attachment-card:hover {
        background-color: #e9ecef !important;
    }
    @media print {
        .income-view .btn,
        .modal-header .btn-close {
            display: none !important;
        }
    }
CSS);
