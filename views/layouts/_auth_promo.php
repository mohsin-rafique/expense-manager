<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Authentication Promotional Panel Partial View
 *
 * Renders the branded promotional panel for auth pages.
 * Content can be customized via view parameters.
 *
 * ## Customization via $this->params
 *
 * ```php
 * // In view file
 * $this->params['authPromoTitle'] = 'Already have an account?';
 * $this->params['authPromoButtonText'] = 'Sign In';
 * $this->params['authPromoButtonUrl'] = ['/site/login'];
 * ```
 *
 * @var yii\web\View $this The view object
 *
 * @see views/layouts/auth.php Parent layout
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\bootstrap5\Html;

// Default values (can be overridden in view via $this->params)
$promoTitle = $this->params['authPromoTitle'] ?? Yii::t('app', 'New Here?');
$promoText = $this->params['authPromoText'] ?? Yii::t('app', 'Join thousands of users who trust Expense Manager to take control of their finances. Track expenses, monitor income, and achieve your financial goals.');
$buttonText = $this->params['authPromoButtonText'] ?? Yii::t('app', 'Create an Account');
$buttonUrl = $this->params['authPromoButtonUrl'] ?? ['/site/signup'];
$features = $this->params['authPromoFeatures'] ?? [
    Yii::t('app', 'Track daily expenses effortlessly'),
    Yii::t('app', 'Categorize income and spending'),
    Yii::t('app', 'Generate insightful financial reports'),
    Yii::t('app', 'Secure and private — your data stays yours'),
];
?>

<!-- ============================================================== -->
<!-- Promotional Panel                                              -->
<!-- ============================================================== -->
<div class="card col-md-5 text-white bg-primary py-5 mb-0 border-0 rounded-0 rounded-end">
    <div class="card-body d-flex flex-column justify-content-center text-center">

        <!-- App Logo (SVG) -->
        <div class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
                <defs>
                    <linearGradient id="iconGradPromo" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#60a5fa" />
                        <stop offset="100%" style="stop-color:#4ade80" />
                    </linearGradient>
                </defs>
                <rect x="6" y="18" width="52" height="42" rx="8" fill="url(#iconGradPromo)" />
                <path d="M12 18 Q12 8 22 8 L42 8 Q52 8 52 18" fill="none" stroke="url(#iconGradPromo)" stroke-width="5" stroke-linecap="round" />
                <rect x="36" y="30" width="16" height="12" rx="3" fill="#1e3a8a" opacity="0.8" />
                <path d="M16 52 L26 38 L36 46 L50 28" fill="none" stroke="#1e3a8a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M42 28 L50 28 L50 36" fill="none" stroke="#1e3a8a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <!-- Promo Heading -->
        <h2 class="fw-bold mb-3"><?= Html::encode($promoTitle) ?></h2>

        <!-- Promo Description -->
        <p class="mb-4 px-3"><?= Html::encode($promoText) ?></p>

        <!-- Features List -->
        <?php if (!empty($features)): ?>
            <ul class="list-unstyled text-start px-4 mb-4">
                <?php foreach ($features as $feature): ?>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= Html::encode($feature) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- CTA Button -->
        <?= Html::a(
            Html::encode($buttonText),
            $buttonUrl,
            ['class' => 'btn btn-lg btn-outline-light']
        ) ?>

    </div>
</div>
