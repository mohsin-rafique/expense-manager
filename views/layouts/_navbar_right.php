<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Right Navigation Partial View
 *
 * Renders the right-side navigation items:
 * - For guests: Login and Sign Up buttons
 * - For authenticated users: Notifications and User menu
 *
 * @var yii\web\View $this The view object
 * @var bool $isGuest Whether user is a guest
 * @var string $displayName User's display name (profile name or username)
 * @var string $initials User's initials for avatar
 * @var string $userEmail User's email address
 * @var string|null $avatarUrl User's avatar URL (custom or Gravatar)
 *
 * @see views/layouts/_navbar.php Parent view
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Url;
?>

<ul class="navbar-nav">
    <?php if ($isGuest): ?>
        <!-- Guest Navigation -->
        <li class="nav-item">
            <a class="nav-link" href="<?= Url::to(['/site/login']) ?>">
                <i class="bi bi-box-arrow-in-right nav-icon"></i>
                <span><?= Yii::t('app', 'Login') ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link btn btn-success text-white ms-2 px-3" href="<?= Url::to(['/site/signup']) ?>">
                <i class="bi bi-person-plus nav-icon"></i>
                <span><?= Yii::t('app', 'Sign Up') ?></span>
            </a>
        </li>
    <?php else: ?>
        <!-- User Menu -->
        <?= $this->render('_navbar_user', [
            'displayName' => $displayName,
            'initials' => $initials,
            'userEmail' => $userEmail,
            'avatarUrl' => $avatarUrl,
        ]) ?>
    <?php endif; ?>
</ul>
