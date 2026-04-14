<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Left Navigation Partial View
 *
 * Renders the main navigation menu items:
 * - Dashboard link
 * - Income dropdown (All Income, Categories)
 * - Expenses dropdown (All Expenses, Categories)
 *
 * @var yii\web\View $this The view object
 * @var string $currentController Current controller ID for active state
 * @var string $currentAction Current action ID for active state
 * @var bool $isGuest Whether user is a guest
 *
 * @see views/layouts/_navbar.php Parent view
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Url;
?>

<ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link <?= $currentController === 'site' && $currentAction === 'index' ? 'active' : '' ?>" href="<?= Url::to(['/site']) ?>">
            <i class="bi bi-house nav-icon"></i>
            <span><?= Yii::t('app', 'Dashboard') ?></span>
        </a>
    </li>

    <?php if (!$isGuest): ?>
        <!-- Income Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($currentController, ['income', 'income-category']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-graph-up-arrow nav-icon text-success"></i>
                <span><?= Yii::t('app', 'Income') ?></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item <?= $currentController === 'income' ? 'active' : '' ?>" href="<?= Url::to(['/income']) ?>">
                        <i class="bi bi-cash-stack text-success"></i>
                        <span><?= Yii::t('app', 'All Income') ?></span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $currentController === 'income-category' ? 'active' : '' ?>" href="<?= Url::to(['/income-category']) ?>">
                        <i class="bi bi-folder text-success"></i>
                        <span><?= Yii::t('app', 'Income Categories') ?></span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Expenses Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($currentController, ['expense', 'expense-category']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-graph-down-arrow nav-icon text-danger"></i>
                <span><?= Yii::t('app', 'Expenses') ?></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item <?= $currentController === 'expense' ? 'active' : '' ?>" href="<?= Url::to(['/expense']) ?>">
                        <i class="bi bi-credit-card text-danger"></i>
                        <span><?= Yii::t('app', 'All Expenses') ?></span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $currentController === 'expense-category' ? 'active' : '' ?>" href="<?= Url::to(['/expense-category']) ?>">
                        <i class="bi bi-folder text-danger"></i>
                        <span><?= Yii::t('app', 'Expense Categories') ?></span>
                    </a>
                </li>
            </ul>
        </li>
    <?php endif; ?>
</ul>
