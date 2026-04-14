<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Profile Settings View
 *
 * Comprehensive settings page with:
 * - Avatar and banner upload (AJAX)
 * - Personal details form
 * - Password change
 * - Currency settings
 * - Database backups
 *
 * @var yii\web\View $this
 * @var string $activeTab
 * @var app\models\User $userModel
 * @var app\models\Profile $profileModel
 * @var app\models\ChangePasswordForm $changePasswordModel
 * @var app\models\Settings $currencySettingsModel
 * @var array $files
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use app\components\CurrencyFormatter;
use app\helpers\Timezone;

$this->title = Yii::t('app', 'Settings');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Profile'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$displayName = $profileModel ? $profileModel->getDisplayName() : $userModel->username;
$initials = $profileModel ? $profileModel->getInitials() : strtoupper(substr($userModel->username, 0, 2));
$avatarUrl = $profileModel ? $profileModel->getAvatarUrl(120) : null;
$bannerUrl = $profileModel ? $profileModel->getBannerUrl() : null;
$hasCustomAvatar = $profileModel && $profileModel->hasCustomAvatar();
$hasCustomBanner = $profileModel && $profileModel->hasCustomBanner();

// URLs for AJAX upload
$uploadAvatarUrl = Url::to(['upload-avatar']);
$deleteAvatarUrl = Url::to(['delete-avatar']);
$uploadBannerUrl = Url::to(['upload-banner']);
$deleteBannerUrl = Url::to(['delete-banner']);
?>

<!-- Settings Header -->
<div class="settings-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="settings-title">
                <i class="bi bi-gear text-primary me-2"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="settings-subtitle text-muted mb-0">
                <?= Yii::t('app', 'Manage your account settings and preferences') ?>
            </p>
        </div>
        <div class="col-auto">
            <?= Html::a(
                '<i class="bi bi-arrow-left me-1"></i>' . Yii::t('app', 'Back to Profile'),
                ['index'],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <!-- Profile Summary Card with Avatar Upload -->
        <div class="card settings-profile-card mb-4">
            <div class="card-body text-center">
                <!-- Avatar Upload Area -->
                <div class="settings-avatar-wrapper mb-3" id="avatar-upload-area">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= Html::encode($avatarUrl) ?>" class="settings-avatar" id="avatar-preview" alt="<?= Html::encode($displayName) ?>">
                    <?php else: ?>
                        <div class="settings-avatar settings-avatar-initials" id="avatar-initials"><?= Html::encode($initials) ?></div>
                        <img src="" class="settings-avatar d-none" id="avatar-preview" alt="">
                    <?php endif; ?>
                    <label for="avatar-file-input" class="settings-avatar-edit" title="<?= Yii::t('app', 'Change Avatar') ?>">
                        <i class="bi bi-camera"></i>
                    </label>
                    <input type="file" id="avatar-file-input" class="d-none" accept="image/png,image/jpeg,image/gif,image/webp">
                    <div class="avatar-loading d-none" id="avatar-loading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>

                <?php if ($hasCustomAvatar): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger mb-3" id="delete-avatar-btn">
                        <i class="bi bi-trash me-1"></i><?= Yii::t('app', 'Remove Avatar') ?>
                    </button>
                <?php endif; ?>

                <h5 class="settings-profile-name mb-1"><?= Html::encode($displayName) ?></h5>
                <p class="settings-profile-email text-muted mb-0"><?= Html::encode($userModel->email) ?></p>

                <!-- Banner Upload Section -->
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-muted"><?= Yii::t('app', 'Cover Image') ?></small>
                        <?php if ($hasCustomBanner): ?>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" id="delete-banner-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="banner-upload-area" id="banner-upload-area">
                        <?php if ($bannerUrl): ?>
                            <img src="<?= Html::encode($bannerUrl) ?>" class="banner-preview" id="banner-preview" alt="">
                        <?php else: ?>
                            <div class="banner-placeholder" id="banner-placeholder">
                                <i class="bi bi-image"></i>
                                <span><?= Yii::t('app', 'Upload Cover') ?></span>
                            </div>
                            <img src="" class="banner-preview d-none" id="banner-preview" alt="">
                        <?php endif; ?>
                        <input type="file" id="banner-file-input" class="d-none" accept="image/png,image/jpeg,image/webp">
                        <div class="banner-loading d-none" id="banner-loading">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Navigation -->
        <div class="card settings-nav-card">
            <div class="card-body p-0">
                <nav class="settings-nav nav flex-column nav-pills" id="settings-tabs" role="tablist">
                    <a class="settings-nav-item nav-link<?= $activeTab === 'personalDetails' ? ' active' : '' ?>" id="personalDetails-tab" data-bs-toggle="pill" href="#personalDetails" role="tab">
                        <i class="bi bi-person"></i>
                        <span><?= Yii::t('app', 'Personal Details') ?></span>
                    </a>
                    <a class="settings-nav-item nav-link<?= $activeTab === 'changePassword' ? ' active' : '' ?>" id="changePassword-tab" data-bs-toggle="pill" href="#changePassword" role="tab">
                        <i class="bi bi-shield-lock"></i>
                        <span><?= Yii::t('app', 'Change Password') ?></span>
                    </a>
                    <a class="settings-nav-item nav-link<?= $activeTab === 'currencySettings' ? ' active' : '' ?>" id="currencySettings-tab" data-bs-toggle="pill" href="#currencySettings" role="tab">
                        <i class="bi bi-currency-exchange"></i>
                        <span><?= Yii::t('app', 'Currency Settings') ?></span>
                    </a>
                    <a class="settings-nav-item nav-link<?= $activeTab === 'backups' ? ' active' : '' ?>" id="backups-tab" data-bs-toggle="pill" href="#backups" role="tab">
                        <i class="bi bi-cloud-arrow-down"></i>
                        <span><?= Yii::t('app', 'Backups') ?></span>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Right Content Area -->
    <div class="col-lg-9">
        <div class="tab-content" id="settings-tabContent">
            <!-- Personal Details Tab -->
            <div class="tab-pane fade<?= $activeTab === 'personalDetails' ? ' show active' : '' ?>" id="personalDetails" role="tabpanel">
                <div class="card settings-content-card">
                    <div class="card-header">
                        <h5 class="card-title mb-2"><i class="bi bi-person-circle text-primary me-2"></i><?= Yii::t('app', 'Personal Details') ?></h5>
                        <p class="card-subtitle text-muted mb-0"><?= Yii::t('app', 'Update your personal information') ?></p>
                    </div>
                    <div class="card-body">
                        <?php $form = ActiveForm::begin(['id' => 'profile-form', 'options' => ['class' => 'settings-form']]); ?>
                        <div class="row g-3">
                            <div class="col-md-6"><?= $form->field($profileModel, 'name')->textInput(['class' => 'form-control', 'placeholder' => Yii::t('app', 'Enter your full name')])->label('<i class="bi bi-person me-1 text-muted"></i>' . Yii::t('app', 'Full Name')) ?></div>
                            <div class="col-md-6"><?= $form->field($profileModel, 'gravatar_email')->textInput(['class' => 'form-control', 'placeholder' => 'email@example.com'])->label('<i class="bi bi-envelope me-1 text-muted"></i>' . Yii::t('app', 'Gravatar Email'))->hint(Yii::t('app', 'Fallback avatar if no custom upload')) ?></div>
                            <div class="col-md-6"><?= $form->field($profileModel, 'designation')->textInput(['class' => 'form-control', 'placeholder' => Yii::t('app', 'e.g., Software Engineer')])->label('<i class="bi bi-briefcase me-1 text-muted"></i>' . Yii::t('app', 'Designation')) ?></div>
                            <div class="col-md-6"><?= $form->field($profileModel, 'phone')->textInput(['class' => 'form-control', 'placeholder' => '+1 (555) 123-4567'])->label('<i class="bi bi-telephone me-1 text-muted"></i>' . Yii::t('app', 'Phone Number')) ?></div>
                            <div class="col-md-6"><?= $form->field($profileModel, 'location')->textInput(['class' => 'form-control', 'placeholder' => Yii::t('app', 'e.g., New York, USA')])->label('<i class="bi bi-geo-alt me-1 text-muted"></i>' . Yii::t('app', 'Location')) ?></div>
                            <div class="col-md-6"><?= $form->field($profileModel, 'website')->textInput(['class' => 'form-control', 'placeholder' => 'https://yourwebsite.com'])->label('<i class="bi bi-globe me-1 text-muted"></i>' . Yii::t('app', 'Website')) ?></div>
                            <div class="col-md-6"><?= $form->field($profileModel, 'timezone')->dropDownList(ArrayHelper::map(Timezone::getAll(), 'timezone', 'name'), ['class' => 'form-select', 'prompt' => Yii::t('app', '— Select Timezone —')])->label('<i class="bi bi-clock me-1 text-muted"></i>' . Yii::t('app', 'Timezone')) ?></div>
                            <div class="col-12"><?= $form->field($profileModel, 'bio')->textarea(['class' => 'form-control', 'rows' => 4, 'placeholder' => Yii::t('app', 'Tell us about yourself...')])->label('<i class="bi bi-card-text me-1 text-muted"></i>' . Yii::t('app', 'Bio')) ?></div>
                        </div>
                        <div class="settings-form-actions"><?= Html::submitButton('<i class="bi bi-check-lg me-1"></i>' . Yii::t('app', 'Save Changes'), ['class' => 'btn btn-primary']) ?></div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div class="tab-pane fade<?= $activeTab === 'changePassword' ? ' show active' : '' ?>" id="changePassword" role="tabpanel">
                <div class="card settings-content-card">
                    <div class="card-header">
                        <h5 class="card-title mb-2"><i class="bi bi-shield-lock text-warning me-2"></i><?= Yii::t('app', 'Change Password') ?></h5>
                        <p class="card-subtitle text-muted mb-0"><?= Yii::t('app', 'Ensure your account is using a strong password') ?></p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info d-flex align-items-start mb-4">
                            <i class="bi bi-info-circle me-2 mt-1"></i>
                            <div><strong><?= Yii::t('app', 'Password Requirements:') ?></strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <li><?= Yii::t('app', 'At least 8 characters long') ?></li>
                                    <li><?= Yii::t('app', 'Contains uppercase and lowercase letters') ?></li>
                                    <li><?= Yii::t('app', 'Contains at least one number') ?></li>
                                </ul>
                            </div>
                        </div>
                        <?php $form = ActiveForm::begin(['id' => 'change-password-form', 'options' => ['class' => 'settings-form']]); ?>
                        <div class="row g-3">
                            <div class="col-md-12"><?= $form->field($changePasswordModel, 'oldPassword')->passwordInput(['class' => 'form-control', 'placeholder' => Yii::t('app', 'Enter current password')])->label('<i class="bi bi-key me-1 text-muted"></i>' . Yii::t('app', 'Current Password')) ?></div>
                            <div class="col-md-6"><?= $form->field($changePasswordModel, 'newPassword')->passwordInput(['class' => 'form-control', 'placeholder' => Yii::t('app', 'Enter new password')])->label('<i class="bi bi-lock me-1 text-muted"></i>' . Yii::t('app', 'New Password')) ?></div>
                            <div class="col-md-6"><?= $form->field($changePasswordModel, 'confirmPassword')->passwordInput(['class' => 'form-control', 'placeholder' => Yii::t('app', 'Confirm new password')])->label('<i class="bi bi-lock-fill me-1 text-muted"></i>' . Yii::t('app', 'Confirm Password')) ?></div>
                        </div>
                        <div class="settings-form-actions"><?= Html::submitButton('<i class="bi bi-shield-check me-1"></i>' . Yii::t('app', 'Update Password'), ['class' => 'btn btn-warning']) ?></div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>

            <!-- Currency Settings Tab -->
            <div class="tab-pane fade<?= $activeTab === 'currencySettings' ? ' show active' : '' ?>" id="currencySettings" role="tabpanel">
                <div class="card settings-content-card">
                    <div class="card-header">
                        <h5 class="card-title mb-2"><i class="bi bi-currency-exchange text-success me-2"></i><?= Yii::t('app', 'Currency Settings') ?></h5>
                        <p class="card-subtitle text-muted mb-0"><?= Yii::t('app', 'Configure how currency values are displayed') ?></p>
                    </div>
                    <div class="card-body">
                        <div class="currency-preview-card mb-4">
                            <div class="currency-preview-label"><?= Yii::t('app', 'Preview') ?></div>
                            <div class="currency-preview-value" id="currency-preview"><?= Yii::$app->currency->format(12345.67) ?></div>
                        </div>
                        <?php $form = ActiveForm::begin(['id' => 'currency-settings-form', 'options' => ['class' => 'settings-form']]); ?>
                        <div class="row g-3">
                            <div class="col-md-6"><?= $form->field($currencySettingsModel, 'currency')->dropDownList(CurrencyFormatter::getCurrencyCodes(), ['class' => 'form-select', 'prompt' => Yii::t('app', '— Select Currency —')])->label('<i class="bi bi-currency-dollar me-1 text-muted"></i>' . Yii::t('app', 'Currency')) ?></div>
                            <div class="col-md-6"><?= $form->field($currencySettingsModel, 'currency_position')->dropDownList(CurrencyFormatter::getPositionOptions(), ['class' => 'form-select'])->label('<i class="bi bi-text-left me-1 text-muted"></i>' . Yii::t('app', 'Symbol Position')) ?></div>
                            <div class="col-md-4"><?= $form->field($currencySettingsModel, 'thousand_separator')->textInput(['class' => 'form-control', 'placeholder' => ',', 'maxlength' => 1])->label('<i class="bi bi-hash me-1 text-muted"></i>' . Yii::t('app', 'Thousand Separator'))->hint(Yii::t('app', 'e.g., comma (,) or period (.)')) ?></div>
                            <div class="col-md-4"><?= $form->field($currencySettingsModel, 'decimal_separator')->textInput(['class' => 'form-control', 'placeholder' => '.', 'maxlength' => 1])->label('<i class="bi bi-dot me-1 text-muted"></i>' . Yii::t('app', 'Decimal Separator'))->hint(Yii::t('app', 'e.g., period (.) or comma (,)')) ?></div>
                            <div class="col-md-4"><?= $form->field($currencySettingsModel, 'decimal_places')->textInput(['class' => 'form-control', 'type' => 'number', 'min' => 0, 'max' => 4, 'placeholder' => '2'])->label('<i class="bi bi-123 me-1 text-muted"></i>' . Yii::t('app', 'Decimal Places'))->hint(Yii::t('app', '0 to 4 decimal places')) ?></div>
                        </div>
                        <div class="settings-form-actions"><?= Html::submitButton('<i class="bi bi-check-lg me-1"></i>' . Yii::t('app', 'Save Settings'), ['class' => 'btn btn-success']) ?></div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>

            <!-- Backups Tab -->
            <div class="tab-pane fade<?= $activeTab === 'backups' ? ' show active' : '' ?>" id="backups" role="tabpanel">
                <div class="card settings-content-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-2"><i class="bi bi-cloud-arrow-up text-info me-2"></i><?= Yii::t('app', 'Create Backup') ?></h5>
                        <p class="card-subtitle text-muted mb-0"><?= Yii::t('app', 'Export your data to an SQL file') ?></p>
                    </div>
                    <div class="card-body">
                        <div class="backup-info-box mb-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="backup-info-icon"><i class="bi bi-database"></i></div>
                                </div>
                                <div class="col">
                                    <h6 class="mb-1"><?= Yii::t('app', 'Database Export') ?></h6>
                                    <p class="text-muted mb-0 small"><?= Yii::t('app', 'Creates an SQL file containing all your expenses, income, categories, and settings.') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php $form = ActiveForm::begin(['id' => 'export-database-form']); ?>
                        <?= Html::hiddenInput('form-type', 'export-database') ?>
                        <?= Html::submitButton('<i class="bi bi-download me-1"></i>' . Yii::t('app', 'Export Database Now'), ['class' => 'btn btn-info']) ?>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>

                <div class="card settings-content-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-2"><i class="bi bi-clock-history text-secondary me-2"></i><?= Yii::t('app', 'Backup History') ?></h5>
                            <p class="card-subtitle text-muted mb-0"><?= Yii::t('app', 'Previously exported database files') ?></p>
                        </div>
                        <?php if (!empty($files)):
                            ?><span class="badge bg-secondary"><?= count($files) ?> <?= Yii::t('app', 'files') ?></span><?php
                        endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($files)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th><?= Yii::t('app', 'Filename') ?></th>
                                            <th style="width: 180px;"><?= Yii::t('app', 'Created') ?></th>
                                            <th style="width: 120px;" class="text-end"><?= Yii::t('app', 'Actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($files as $index => $file): ?>
                                            <tr>
                                                <td class="text-muted"><?= $index + 1 ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center"><i class="bi bi-file-earmark-code text-primary me-2"></i><span class="fw-medium"><?= Html::encode($file['name']) ?></span></div>
                                                </td>
                                                <td><span class="text-muted"><?= Yii::$app->formatter->asDatetime($file['modified'], 'medium') ?></span></td>
                                                <td class="text-end"><a href="<?= Url::to('@web/sql-exports/' . $file['name']) ?>" class="btn btn-sm btn-outline-primary" download title="<?= Yii::t('app', 'Download') ?>"><i class="bi bi-download"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state py-5">
                                <div class="empty-state-icon mb-3"><i class="bi bi-cloud-slash"></i></div>
                                <h5 class="empty-state-title"><?= Yii::t('app', 'No Backups Yet') ?></h5>
                                <p class="empty-state-text text-muted"><?= Yii::t('app', 'Create your first backup using the button above.') ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript for Avatar and Banner Upload
$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;

$js = <<<JS
(function() {
    'use strict';
    var csrfParam = '{$csrfParam}';
    var csrfToken = '{$csrfToken}';

    // Avatar Upload
    var avatarInput = document.getElementById('avatar-file-input');
    var avatarPreview = document.getElementById('avatar-preview');
    var avatarInitials = document.getElementById('avatar-initials');
    var avatarLoading = document.getElementById('avatar-loading');
    var deleteAvatarBtn = document.getElementById('delete-avatar-btn');

    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                NEM.Toast.error('Please select a valid image file (PNG, JPG, GIF, or WebP).');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                NEM.Toast.error('Avatar file size cannot exceed 2MB.');
                return;
            }
            if (avatarLoading) avatarLoading.classList.remove('d-none');
            var formData = new FormData();
            formData.append('avatarFile', file);
            formData.append(csrfParam, csrfToken);
            fetch('{$uploadAvatarUrl}', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (avatarLoading) avatarLoading.classList.add('d-none');
                    if (data.success) {
                        if (avatarPreview) { avatarPreview.src = data.avatarUrl; avatarPreview.classList.remove('d-none'); }
                        if (avatarInitials) avatarInitials.classList.add('d-none');
                        NEM.Toast.success(data.message);
                        if (!deleteAvatarBtn) location.reload();
                    } else {
                        NEM.Toast.error(data.message);
                    }
                })
                .catch(function() { if (avatarLoading) avatarLoading.classList.add('d-none'); NEM.Toast.error('Upload failed.'); });
            avatarInput.value = '';
        });
    }

    if (deleteAvatarBtn) {
        deleteAvatarBtn.addEventListener('click', function() {
            if (!confirm('Remove your avatar?')) return;
            var formData = new FormData();
            formData.append(csrfParam, csrfToken);
            fetch('{$deleteAvatarUrl}', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) { if (data.success) { NEM.Toast.success(data.message); location.reload(); } else { NEM.Toast.error(data.message); } })
                .catch(function() { NEM.Toast.error('Delete failed.'); });
        });
    }

    // Banner Upload
    var bannerArea = document.getElementById('banner-upload-area');
    var bannerInput = document.getElementById('banner-file-input');
    var bannerPreview = document.getElementById('banner-preview');
    var bannerPlaceholder = document.getElementById('banner-placeholder');
    var bannerLoading = document.getElementById('banner-loading');
    var deleteBannerBtn = document.getElementById('delete-banner-btn');

    if (bannerArea) {
        bannerArea.addEventListener('click', function(e) { if (e.target !== deleteBannerBtn) bannerInput.click(); });
    }

    if (bannerInput) {
        bannerInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
                NEM.Toast.error('Please select PNG, JPG, or WebP.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                NEM.Toast.error('Banner file size cannot exceed 5MB.');
                return;
            }
            if (bannerLoading) bannerLoading.classList.remove('d-none');
            var formData = new FormData();
            formData.append('bannerFile', file);
            formData.append(csrfParam, csrfToken);
            fetch('{$uploadBannerUrl}', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (bannerLoading) bannerLoading.classList.add('d-none');
                    if (data.success) {
                        if (bannerPreview) { bannerPreview.src = data.bannerUrl; bannerPreview.classList.remove('d-none'); }
                        if (bannerPlaceholder) bannerPlaceholder.classList.add('d-none');
                        NEM.Toast.success(data.message);
                        if (!deleteBannerBtn) location.reload();
                    } else {
                        NEM.Toast.error(data.message);
                    }
                })
                .catch(function() { if (bannerLoading) bannerLoading.classList.add('d-none'); NEM.Toast.error('Upload failed.'); });
            bannerInput.value = '';
        });
    }

    if (deleteBannerBtn) {
        deleteBannerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('Remove cover image?')) return;
            var formData = new FormData();
            formData.append(csrfParam, csrfToken);
            fetch('{$deleteBannerUrl}', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) { if (data.success) { NEM.Toast.success(data.message); location.reload(); } else { NEM.Toast.error(data.message); } })
                .catch(function() { NEM.Toast.error('Delete failed.'); });
        });
    }
})();
JS;
$this->registerJs($js);

$css = <<<CSS
.banner-upload-area { position: relative; height: 80px; border: 2px dashed var(--bs-border-color); border-radius: 8px; overflow: hidden; cursor: pointer; transition: border-color 0.2s; }
.banner-upload-area:hover { border-color: var(--bs-primary); }
.banner-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--bs-secondary); }
.banner-placeholder i { font-size: 1.5rem; margin-bottom: 0.25rem; }
.banner-placeholder span { font-size: 0.75rem; }
.banner-preview { width: 100%; height: 100%; object-fit: cover; }
.banner-loading, .avatar-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.8); padding: 10px; border-radius: 50%; }
.settings-avatar-wrapper { position: relative; display: inline-block; }
.settings-avatar-edit { position: absolute; bottom: 5px; right: 5px; width: 32px; height: 32px; background: var(--bs-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color 0.2s; border: 2px solid white; }
.settings-avatar-edit:hover { background: #0056b3; }
CSS;
$this->registerCss($css);
