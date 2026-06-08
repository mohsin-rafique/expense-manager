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
 * - Language switcher
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
use app\widgets\LanguageSwitcher;
?>

<ul class="navbar-nav align-items-center">
    <?php if (!$isGuest): ?>
        <?php
        $workspaceManager = \Yii::$app->workspace;
        $activeWorkspace = $workspaceManager->getWorkspace();
        $workspaces = $workspaceManager->getAll();
        ?>
        <?php if ($activeWorkspace !== null): ?>
            <!-- Workspace Switcher -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="workspaceDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi <?= $activeWorkspace->is_personal ? 'bi-person-circle' : 'bi-people-fill' ?> nav-icon"></i>
                    <span class="d-none d-lg-inline text-truncate" style="max-width: 140px;"><?= \yii\helpers\Html::encode($activeWorkspace->name) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="workspaceDropdown">
                    <li><h6 class="dropdown-header"><?= \Yii::t('app', 'Workspaces') ?></h6></li>
                    <?php foreach ($workspaces as $ws): ?>
                        <?php $isActive = $ws->id === $activeWorkspace->id; ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center<?= $isActive ? ' active' : '' ?>" href="<?= Url::to(['/workspace/switch', 'id' => $ws->id]) ?>" data-pjax="0">
                                <i class="bi <?= $ws->is_personal ? 'bi-person' : 'bi-people' ?> me-2"></i>
                                <span class="text-truncate"><?= \yii\helpers\Html::encode($ws->name) ?></span>
                                <?php if ($isActive): ?>
                                    <i class="bi bi-check-lg ms-auto"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="<?= Url::to(['/workspace/index']) ?>" data-pjax="0">
                            <i class="bi bi-gear me-2 text-muted"></i><?= \Yii::t('app', 'Team & Workspaces') ?>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Language Switcher -->
    <li class="nav-item dropdown">
        <?= LanguageSwitcher::widget(['style' => 'dropdown']) ?>
    </li>

    <?php if ($isGuest): ?>
        <!-- Guest Navigation -->
        <li class="nav-item">
            <a class="nav-link" href="<?= Url::to(['/site/login']) ?>">
                <i class="bi bi-box-arrow-in-right nav-icon"></i>
                <span><?= \Yii::t('app', 'Login') ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link btn btn-success text-white ms-2 px-3" href="<?= Url::to(['/site/signup']) ?>">
                <i class="bi bi-person-plus nav-icon"></i>
                <span><?= \Yii::t('app', 'Sign Up') ?></span>
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
