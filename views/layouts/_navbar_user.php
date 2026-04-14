<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * User Menu Dropdown Partial View
 *
 * Renders the user profile dropdown menu with:
 * - User avatar (custom upload or Gravatar fallback) and display name
 * - Profile link
 * - Settings link
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
    <ul class="dropdown-menu dropdown-menu-end">
        <!-- User Info Header -->
        <li class="px-3 py-2 border-bottom">
            <div class="d-flex align-items-center">
                <?php if (!empty($avatarUrl)): ?>
                    <img src="<?= Html::encode($avatarUrl) ?>" alt="<?= Html::encode($displayName) ?>" class="user-avatar-img me-2">
                <?php else: ?>
                    <span class="user-avatar me-2">
                        <?= Html::encode($initials) ?>
                    </span>
                <?php endif; ?>
                <div>
                    <div class="fw-semibold"><?= Html::encode($displayName) ?></div>
                    <small class="text-muted"><?= Html::encode($userEmail) ?></small>
                </div>
            </div>
        </li>

        <!-- Profile Link -->
        <li>
            <a class="dropdown-item" href="<?= Url::to(['/profile']) ?>">
                <i class="bi bi-person text-muted"></i>
                <span><?= Yii::t('app', 'My Profile') ?></span>
            </a>
        </li>

        <!-- Settings Link -->
        <li>
            <a class="dropdown-item" href="<?= Url::to(['/profile/settings']) ?>">
                <i class="bi bi-gear text-muted"></i>
                <span><?= Yii::t('app', 'Settings') ?></span>
            </a>
        </li>

        <li>
            <hr class="dropdown-divider">
        </li>

        <!-- Logout Button -->
        <li>
            <?= Html::beginForm(['/site/logout'], 'post') ?>
            <button type="submit" class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span><?= Yii::t('app', 'Logout') ?></span>
            </button>
            <?= Html::endForm() ?>
        </li>
    </ul>
</li>
