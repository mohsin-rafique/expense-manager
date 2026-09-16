<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Profile Index View
 *
 * Displays user profile information in a professional layout with:
 * - Gradient banner header with avatar, name and meta
 * - Personal information card
 * - About card with quick details
 * - Account overview statistics
 * - Quick actions
 *
 * @var yii\web\View $this
 * @var app\models\User $user
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;

$this->title = Yii::t('app', 'My Profile');
$this->params['breadcrumbs'][] = $this->title;

$profile = $user->profile;
$displayName = $profile ? $profile->getDisplayName() : $user->username;
$initials = $profile ? $profile->getInitials() : strtoupper(substr($user->username, 0, 2));
$avatarUrl = $profile ? $profile->getAvatarUrl(230) : null;

// Quick action definitions (icon, tint, label, subtitle, tab)
$quickActions = [
    ['icon' => 'bi-person-gear', 'tint' => 'primary', 'label' => Yii::t('app', 'Edit Profile'), 'subtitle' => Yii::t('app', 'Update your personal details'), 'tab' => 'personalDetails'],
    ['icon' => 'bi-shield-lock', 'tint' => 'danger', 'label' => Yii::t('app', 'Change Password'), 'subtitle' => Yii::t('app', 'Secure your account'), 'tab' => 'changePassword'],
    ['icon' => 'bi-currency-dollar', 'tint' => 'success', 'label' => Yii::t('app', 'Currency Settings'), 'subtitle' => Yii::t('app', 'Manage currency preferences'), 'tab' => 'currencySettings'],
    ['icon' => 'bi-cloud-download', 'tint' => 'info', 'label' => Yii::t('app', 'Backup Data'), 'subtitle' => Yii::t('app', 'Download your data'), 'tab' => 'backups'],
];
?>

<!-- ============================================================== -->
<!-- Profile Banner                                                 -->
<!-- ============================================================== -->
<div class="profile-banner mb-4">
    <div class="profile-banner-body">
        <div class="profile-banner-main">
            <div class="profile-avatar-wrapper">
                <?php if ($avatarUrl): ?>
                    <?= Html::img($avatarUrl, [
                        'class' => 'profile-avatar',
                        'alt' => Html::encode($displayName),
                    ]) ?>
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-initials">
                        <?= Html::encode($initials) ?>
                    </div>
                <?php endif; ?>
                <span class="profile-status online" title="<?= Yii::t('app', 'Online') ?>"></span>
            </div>

            <div class="profile-banner-info">
                <h1 class="profile-name"><?= Html::encode($displayName) ?></h1>
                <?php if ($profile && !empty($profile->designation)): ?>
                    <p class="profile-designation"><?= Html::encode($profile->designation) ?></p>
                <?php endif; ?>
                <div class="profile-meta">
                    <?php if ($profile && !empty($profile->location)): ?>
                        <span class="profile-meta-item">
                            <i class="bi bi-geo-alt"></i>
                            <?= Html::encode($profile->location) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($profile && !empty($profile->timezone)): ?>
                        <span class="profile-meta-item">
                            <i class="bi bi-clock"></i>
                            <?= Html::encode($profile->timezone) ?>
                        </span>
                    <?php endif; ?>
                    <span class="profile-meta-item">
                        <i class="bi bi-calendar3"></i>
                        <?= Yii::t('app', 'Joined {date}', ['date' => Yii::$app->formatter->asDate($user->created_at, 'medium')]) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="profile-banner-actions">
            <?= Html::a(
                '<i class="bi bi-pencil-square me-1"></i>' . Yii::t('app', 'Edit Profile'),
                ['settings'],
                ['class' => 'btn profile-edit-btn']
            ) ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ========================================================== -->
    <!-- Left Column - Personal Information                         -->
    <!-- ========================================================== -->
    <div class="col-lg-5">
        <div class="card profile-card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-circle text-primary me-2"></i>
                    <?= Yii::t('app', 'Personal Information') ?>
                </h5>
            </div>
            <div class="card-body">
                <ul class="profile-info-list">
                    <li class="profile-info-item">
                        <span class="profile-info-icon"><i class="bi bi-person"></i></span>
                        <span class="profile-info-text">
                            <span class="profile-info-label"><?= Yii::t('app', 'Full Name') ?></span>
                            <span class="profile-info-value"><?= Html::encode($displayName) ?></span>
                        </span>
                    </li>
                    <li class="profile-info-item">
                        <span class="profile-info-icon"><i class="bi bi-at"></i></span>
                        <span class="profile-info-text">
                            <span class="profile-info-label"><?= Yii::t('app', 'Username') ?></span>
                            <span class="profile-info-value">@<?= Html::encode($user->username) ?></span>
                        </span>
                    </li>
                    <?php if (!empty($user->email)): ?>
                        <li class="profile-info-item">
                            <span class="profile-info-icon"><i class="bi bi-envelope"></i></span>
                            <span class="profile-info-text">
                                <span class="profile-info-label"><?= Yii::t('app', 'Email') ?></span>
                                <span class="profile-info-value"><?= Html::encode($user->email) ?></span>
                            </span>
                        </li>
                    <?php endif; ?>
                    <?php if ($profile && !empty($profile->phone)): ?>
                        <li class="profile-info-item">
                            <span class="profile-info-icon"><i class="bi bi-telephone"></i></span>
                            <span class="profile-info-text">
                                <span class="profile-info-label"><?= Yii::t('app', 'Phone') ?></span>
                                <span class="profile-info-value"><?= Html::encode($profile->phone) ?></span>
                            </span>
                        </li>
                    <?php endif; ?>
                    <?php if ($profile && !empty($profile->location)): ?>
                        <li class="profile-info-item">
                            <span class="profile-info-icon"><i class="bi bi-geo-alt"></i></span>
                            <span class="profile-info-text">
                                <span class="profile-info-label"><?= Yii::t('app', 'Location') ?></span>
                                <span class="profile-info-value"><?= Html::encode($profile->location) ?></span>
                            </span>
                        </li>
                    <?php endif; ?>
                    <?php if ($profile && !empty($profile->website)): ?>
                        <li class="profile-info-item">
                            <span class="profile-info-icon"><i class="bi bi-globe"></i></span>
                            <span class="profile-info-text">
                                <span class="profile-info-label"><?= Yii::t('app', 'Website') ?></span>
                                <span class="profile-info-value">
                                    <a href="<?= Html::encode($profile->website) ?>" target="_blank" rel="noopener">
                                        <?= Html::encode($profile->website) ?>
                                    </a>
                                </span>
                            </span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- Right Column - About & Account Overview                    -->
    <!-- ========================================================== -->
    <div class="col-lg-7">
        <!-- About Card -->
        <div class="card profile-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle text-info me-2"></i>
                    <?= Yii::t('app', 'About') ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($profile && !empty($profile->bio)): ?>
                    <p class="profile-bio"><?= nl2br(Html::encode($profile->bio)) ?></p>
                <?php else: ?>
                    <p class="text-muted fst-italic">
                        <?= Yii::t('app', 'No bio added yet.') ?>
                        <?= Html::a(Yii::t('app', 'Add one now'), ['settings', 'tab' => 'personalDetails']) ?>
                    </p>
                <?php endif; ?>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="profile-detail-card">
                            <div class="profile-detail-icon bg-primary-subtle text-primary">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="profile-detail-content">
                                <span class="profile-detail-label"><?= Yii::t('app', 'Designation') ?></span>
                                <span class="profile-detail-value">
                                    <?= ($profile && !empty($profile->designation)) ? Html::encode($profile->designation) : '-' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-detail-card">
                            <div class="profile-detail-icon bg-success-subtle text-success">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="profile-detail-content">
                                <span class="profile-detail-label"><?= Yii::t('app', 'Timezone') ?></span>
                                <span class="profile-detail-value">
                                    <?= ($profile && !empty($profile->timezone)) ? Html::encode($profile->timezone) : '-' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Overview Card -->
        <div class="card profile-card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up text-success me-2"></i>
                    <?= Yii::t('app', 'Account Overview') ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="profile-stat-card">
                            <div class="profile-stat-icon bg-danger-subtle">
                                <i class="bi bi-graph-down-arrow text-danger"></i>
                            </div>
                            <div class="profile-stat-content">
                                <span class="profile-stat-value text-danger">
                                    <?= Yii::$app->currency->format(
                                        \app\models\Expense::find()
                                            ->where(['user_id' => $user->id])
                                            ->andWhere(['between', 'expense_date', date('Y-m-01'), date('Y-m-t')])
                                            ->sum('amount') ?? 0
                                    ) ?>
                                </span>
                                <span class="profile-stat-label"><?= Yii::t('app', 'This Month Expenses') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-stat-card">
                            <div class="profile-stat-icon bg-success-subtle">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                            </div>
                            <div class="profile-stat-content">
                                <span class="profile-stat-value text-success">
                                    <?= Yii::$app->currency->format(
                                        \app\models\Income::find()
                                            ->where(['user_id' => $user->id])
                                            ->andWhere(['between', 'entry_date', date('Y-m-01'), date('Y-m-t')])
                                            ->sum('amount') ?? 0
                                    ) ?>
                                </span>
                                <span class="profile-stat-label"><?= Yii::t('app', 'This Month Income') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-stat-card">
                            <div class="profile-stat-icon bg-primary-subtle">
                                <i class="bi bi-calendar-check text-primary"></i>
                            </div>
                            <div class="profile-stat-content">
                                <span class="profile-stat-value text-primary">
                                    <?= Yii::$app->formatter->asRelativeTime($user->created_at) ?>
                                </span>
                                <span class="profile-stat-label"><?= Yii::t('app', 'Member Since') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- Quick Actions (full width)                                 -->
    <!-- ========================================================== -->
    <div class="col-12">
        <div class="card profile-card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning-charge text-warning me-2"></i>
                    <?= Yii::t('app', 'Quick Actions') ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="profile-actions-grid">
                    <?php foreach ($quickActions as $action): ?>
                        <?= Html::a(
                            '<span class="profile-action-icon bg-' . $action['tint'] . '-subtle text-' . $action['tint'] . '">'
                                . '<i class="bi ' . $action['icon'] . '"></i>'
                            . '</span>'
                            . '<span class="profile-action-text">'
                                . '<span class="profile-action-label">' . Html::encode($action['label']) . '</span>'
                                . '<span class="profile-action-subtitle">' . Html::encode($action['subtitle']) . '</span>'
                            . '</span>'
                            . '<i class="bi bi-chevron-right profile-action-arrow"></i>',
                            ['settings', 'tab' => $action['tab']],
                            ['class' => 'profile-action-item']
                        ) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
