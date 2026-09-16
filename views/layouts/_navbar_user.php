<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * User Menu Dropdown Partial View
 *
 * Renders the user profile dropdown menu with:
 * - Header: avatar, display name, email and designation
 * - Quick actions: Edit Profile, Change Password, Currency Settings, Backup Data
 * - Logout button
 *
 * @var yii\web\View $this The view object
 * @var string $displayName User's display name
 * @var string $initials User's initials for avatar fallback
 * @var string $userEmail User's email address
 * @var string|null $avatarUrl User's avatar URL (custom or Gravatar)
 *
 * @see views/layouts/_navbar_right.php Parent view
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$designation = Yii::$app->user->identity?->profile?->designation;

// Quick-action menu items (icon, label, settings tab)
$menuItems = [
    ['icon' => 'bi-person', 'label' => Yii::t('app', 'Profile'), 'tab' => 'personalDetails'],
    ['icon' => 'bi-lock', 'label' => Yii::t('app', 'Change Password'), 'tab' => 'changePassword'],
    ['icon' => 'bi-currency-dollar', 'label' => Yii::t('app', 'Currency Settings'), 'tab' => 'currencySettings'],
    ['icon' => 'bi-cloud-arrow-down', 'label' => Yii::t('app', 'Backup Data'), 'tab' => 'backups'],
];
?>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <?php if (!empty($avatarUrl)): ?>
            <img src="<?= Html::encode($avatarUrl) ?>" alt="<?= Html::encode($displayName) ?>" class="user-avatar-img">
        <?php else: ?>
            <span class="user-avatar">
                <?= Html::encode($initials) ?>
            </span>
        <?php endif; ?>
        <span class="d-none d-md-inline"><?= Html::encode($displayName) ?></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow user-menu" style="min-width: 260px;">
        <!-- User Info Header -->
        <li class="px-3 py-3 border-bottom">
            <div class="d-flex align-items-center">
                <?php if (!empty($avatarUrl)): ?>
                    <img src="<?= Html::encode($avatarUrl) ?>" alt="<?= Html::encode($displayName) ?>" class="user-avatar-img user-avatar-lg me-3">
                <?php else: ?>
                    <span class="user-avatar user-avatar-lg me-3">
                        <?= Html::encode($initials) ?>
                    </span>
                <?php endif; ?>
                <div style="min-width: 0;">
                    <div class="fw-semibold text-truncate"><?= Html::encode($displayName) ?></div>
                    <small class="text-muted d-block text-truncate"><?= Html::encode($userEmail) ?></small>
                    <?php if (!empty($designation)): ?>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary mt-1">
                            <?= Html::encode($designation) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </li>

        <!-- Quick Actions -->
        <?php foreach ($menuItems as $item): ?>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="<?= Url::to(['/profile/settings', 'tab' => $item['tab']]) ?>">
                    <i class="bi <?= $item['icon'] ?> me-2 text-muted"></i>
                    <span><?= Html::encode($item['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>

        <li>
            <hr class="dropdown-divider">
        </li>

        <!-- Logout Button -->
        <li>
            <?= Html::beginForm(['/site/logout'], 'post') ?>
            <button type="submit" class="dropdown-item d-flex align-items-center text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>
                <span><?= Yii::t('app', 'Logout') ?></span>
            </button>
            <?= Html::endForm() ?>
        </li>
    </ul>
</li>
