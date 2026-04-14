<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Profile Settings Tab List (Legacy - for backward compatibility)
 *
 * Note: This file is kept for backward compatibility.
 * The settings.php now includes navigation directly.
 *
 * @var yii\web\View $this
 * @var string $activeTab
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
?>

<div class="card-header border-bottom">
    <ul class="nav nav-pills card-header-pills" role="tablist">
        <li class="nav-item">
            <a class="nav-link<?= $activeTab === 'personalDetails' ? ' active' : '' ?>"
               data-bs-toggle="pill"
               href="#personalDetails"
               role="tab">
                <i class="bi bi-person me-1"></i>
                <?= Yii::t('app', 'Personal Details') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $activeTab === 'changePassword' ? ' active' : '' ?>"
               data-bs-toggle="pill"
               href="#changePassword"
               role="tab">
                <i class="bi bi-shield-lock me-1"></i>
                <?= Yii::t('app', 'Change Password') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $activeTab === 'currencySettings' ? ' active' : '' ?>"
               data-bs-toggle="pill"
               href="#currencySettings"
               role="tab">
                <i class="bi bi-currency-exchange me-1"></i>
                <?= Yii::t('app', 'Currency Settings') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $activeTab === 'backups' ? ' active' : '' ?>"
               data-bs-toggle="pill"
               href="#backups"
               role="tab">
                <i class="bi bi-cloud-arrow-down me-1"></i>
                <?= Yii::t('app', 'Backups') ?>
            </a>
        </li>
    </ul>
</div>
