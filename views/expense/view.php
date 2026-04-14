<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Expense Detail View
 *
 * Display expense details in a modern card layout with:
 * - Key information at a glance
 * - File attachment preview
 * - Audit trail information
 *
 * @var yii\web\View $this
 * @var app\models\Expense $model
 */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Expense #{id}', ['id' => $model->id]);
?>

<div class="expense-view">
    <!-- Amount Header -->
    <div class="text-center mb-4 pb-4 border-bottom">
        <div class="amount-display text-danger mb-2">
            <?= $model->getFormattedAmount() ?>
        </div>
        <div class="text-muted">
            <?= Yii::$app->formatter->asDate($model->expense_date, 'full') ?>
        </div>
    </div>

    <!-- Main Details -->
    <div class="row g-3 mb-4">
        <!-- Category -->
        <div class="col-md-6">
            <div class="detail-item">
                <div class="detail-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-tag"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label"><?= Yii::t('app', 'Category') ?></div>
                    <div class="detail-value"><?= Html::encode($model->expenseCategory->name ?? 'N/A') ?></div>
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="col-md-6">
            <div class="detail-item">
                <div class="detail-icon bg-success bg-opacity-10 text-success">
                    <?php
                    $paymentIcons = [
                        'Cash' => 'bi-cash',
                        'Card' => 'bi-credit-card',
                        'Bank' => 'bi-bank',
                    ];
                    $icon = $paymentIcons[$model->payment_method] ?? 'bi-question';
                    ?>
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label"><?= Yii::t('app', 'Payment Method') ?></div>
                    <div class="detail-value"><?= Html::encode($model->payment_method ?: 'N/A') ?></div>
                </div>
            </div>
        </div>

        <!-- User -->
        <div class="col-md-6">
            <div class="detail-item">
                <div class="detail-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-person"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label"><?= Yii::t('app', 'User') ?></div>
                    <div class="detail-value"><?= Html::encode($model->user->profile->name ?? $model->user->username ?? 'N/A') ?></div>
                </div>
            </div>
        </div>

        <!-- Reference -->
        <div class="col-md-6">
            <div class="detail-item">
                <div class="detail-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hash"></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label"><?= Yii::t('app', 'Reference') ?></div>
                    <div class="detail-value"><?= Html::encode($model->reference ?: '—') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if (!empty($model->description)): ?>
        <div class="mb-4">
            <h6 class="text-muted mb-2">
                <i class="bi bi-text-paragraph me-1"></i><?= Yii::t('app', 'Description') ?>
            </h6>
            <div class="description-box p-3 bg-light rounded">
                <?= nl2br(Html::encode($model->description)) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Attachment -->
    <?php if (!empty($model->filename)): ?>
        <div class="mb-4">
            <h6 class="text-muted mb-2">
                <i class="bi bi-paperclip me-1"></i><?= Yii::t('app', 'Attachment') ?>
            </h6>
            <?php
            $filePath = Yii::getAlias('@webroot/' . $model->filepath);
            $fileExists = file_exists($filePath);
            $fileSize = $fileExists ? filesize($filePath) : 0;
            $fileSizeFormatted = $fileExists ? number_format($fileSize / 1048576, 2) . ' MB' : 'N/A';
            ?>
            <div class="attachment-box p-3 border rounded">
                <div class="d-flex align-items-center">
                    <div class="attachment-icon me-3">
                        <?php if ($model->isPdfFile()): ?>
                            <i class="bi bi-file-pdf display-4 text-danger"></i>
                        <?php else: ?>
                            <i class="bi bi-file-image display-4 text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium"><?= Html::encode($model->filename) ?></div>
                        <small class="text-muted"><?= $fileSizeFormatted ?></small>
                    </div>
                    <?php if ($fileExists): ?>
                        <div class="d-flex gap-2">
                            <?php if ($model->isImageFile()): ?>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#imagePreviewModal">
                                    <i class="bi bi-eye"></i>
                                </button>
                            <?php endif; ?>
                            <a href="<?= Yii::getAlias('@web/' . $model->filepath) ?>"
                                class="btn btn-primary btn-sm"
                                target="_blank"
                                download>
                                <i class="bi bi-download me-1"></i><?= Yii::t('app', 'Download') ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="badge bg-danger"><?= Yii::t('app', 'File not found') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <?php if ($model->isImageFile() && $fileExists): ?>
            <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= Html::encode($model->filename) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="<?= Yii::getAlias('@web/' . $model->filepath) ?>"
                                alt="<?= Html::encode($model->filename) ?>"
                                class="img-fluid rounded">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Audit Trail -->
    <div class="audit-trail pt-3 border-top">
        <h6 class="text-muted mb-3">
            <i class="bi bi-clock-history me-1"></i><?= Yii::t('app', 'Audit Trail') ?>
        </h6>
        <div class="row g-2 small">
            <div class="col-md-6">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><?= Yii::t('app', 'Created At') ?></span>
                    <span><?= !empty($model->created_at) ? Yii::$app->formatter->asDatetime($model->created_at) : '—' ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><?= Yii::t('app', 'Created By') ?></span>
                    <span><?= Html::encode($model->createdBy->profile->name ?? $model->createdBy->username ?? '—') ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><?= Yii::t('app', 'Updated At') ?></span>
                    <span><?= !empty($model->updated_at) ? Yii::$app->formatter->asDatetime($model->updated_at) : '—' ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><?= Yii::t('app', 'Updated By') ?></span>
                    <span><?= Html::encode($model->updatedBy->profile->name ?? $model->updatedBy->username ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$css = <<<CSS
/* Amount Display */
.amount-display {
    font-size: 2.5rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

/* Detail Items */
.detail-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.detail-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 1.25rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.detail-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.detail-value {
    font-weight: 600;
    color: #1f2937;
}

/* Description Box */
.description-box {
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
}

/* Attachment Box */
.attachment-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

/* Audit Trail */
.audit-trail .border-bottom:last-child {
    border-bottom: none !important;
}
CSS;

$this->registerCss($css);
?>
