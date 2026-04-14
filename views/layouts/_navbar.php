<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Navigation Bar Partial View
 *
 * Renders the main navigation bar with:
 * - Brand logo (SVG)
 * - Main navigation links (Dashboard, Income, Expenses)
 * - User menu with profile and logout
 * - Notifications dropdown (optional)
 *
 * @var yii\web\View $this The view object
 *
 * @see views/layouts/main.php Parent layout
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

// Get current controller/action for active menu highlighting
$currentController = Yii::$app->controller->id ?? '';
$currentAction = Yii::$app->controller->action->id ?? '';

// Check if user is guest
$isGuest = Yii::$app->user->isGuest;

// Get user display info (only for authenticated users)
$displayName = '';
$initials = '';
$userEmail = '';
$avatarUrl = null;

if (!$isGuest) {
    $user = Yii::$app->user->identity;

    // Get display name from profile or fallback to username
    $displayName = !empty($user->profile->name) ? $user->profile->name : $user->username;

    // Generate initials from display name
    $initials = strtoupper(substr($displayName, 0, 1));
    if (strpos($displayName, ' ') !== false) {
        $nameParts = explode(' ', $displayName);
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
    }

    // Get user email
    $userEmail = $user->email ?? '';

    // Get avatar URL (custom upload or Gravatar fallback)
    if ($user->profile) {
        $avatarUrl = $user->profile->getAvatarUrl(40);
    }
}
?>

<header id="header">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-main fixed-top">
        <div class="container-xxl">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="<?= Yii::$app->homeUrl ?>">
                <!-- Small icon for mobile -->
                <span class="d-sm-none">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="36" height="36">
                        <defs>
                            <linearGradient id="iconGradNav" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#3b82f6" />
                                <stop offset="50%" style="stop-color:#60a5fa" />
                                <stop offset="100%" style="stop-color:#4ade80" />
                            </linearGradient>
                        </defs>
                        <rect x="6" y="18" width="52" height="42" rx="8" fill="url(#iconGradNav)" />
                        <path d="M12 18 Q12 8 22 8 L42 8 Q52 8 52 18" fill="none" stroke="url(#iconGradNav)" stroke-width="5" stroke-linecap="round" />
                        <rect x="36" y="30" width="16" height="12" rx="3" fill="#1e293b" opacity="0.8" />
                        <path d="M16 52 L26 38 L36 46 L50 28" fill="none" stroke="#1e293b" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M42 28 L50 28 L50 36" fill="none" stroke="#1e293b" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <!-- Full logo for tablet and desktop -->
                <span class="d-none d-sm-inline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 48" height="36">
                        <defs>
                            <linearGradient id="iconGradSmNav" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#3b82f6" />
                                <stop offset="50%" style="stop-color:#60a5fa" />
                                <stop offset="100%" style="stop-color:#4ade80" />
                            </linearGradient>
                        </defs>
                        <g transform="translate(4, 4)">
                            <rect x="2" y="10" width="36" height="28" rx="4" fill="url(#iconGradSmNav)" />
                            <path d="M6 10 Q6 4 12 4 L28 4 Q34 4 34 10" fill="none" stroke="url(#iconGradSmNav)" stroke-width="3" stroke-linecap="round" />
                            <rect x="24" y="18" width="10" height="7" rx="1.5" fill="#1e293b" opacity="0.8" />
                            <path d="M10 32 L16 24 L22 28 L32 18" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M27 18 L32 18 L32 23" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <text x="52" y="32" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="20" font-weight="700" fill="#ffffff">Expense</text>
                        <text x="138" y="32" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="20" font-weight="400" fill="#ffffff">Manager</text>
                    </svg>
                </span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="<?= Yii::t('app', 'Toggle navigation') ?>">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Items -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <!-- Left Navigation -->
                <?= $this->render('_navbar_left', [
                    'currentController' => $currentController,
                    'currentAction' => $currentAction,
                    'isGuest' => $isGuest,
                ]) ?>

                <!-- Right Navigation -->
                <?= $this->render('_navbar_right', [
                    'isGuest' => $isGuest,
                    'displayName' => $displayName,
                    'initials' => $initials,
                    'userEmail' => $userEmail,
                    'avatarUrl' => $avatarUrl,
                ]) ?>
            </div>
        </div>
    </nav>
</header>
