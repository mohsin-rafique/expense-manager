<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Workspace invitation email (HTML).
 *
 * @var yii\web\View $this
 * @var app\models\Workspace $workspace
 * @var app\models\User|null $inviter
 * @var string $acceptUrl
 * @var bool $isExistingUser
 * @var string $role
 */

use yii\helpers\Html;

$inviterName = $inviter->username ?? Yii::$app->name;
?>
<div style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; max-width: 560px; margin: 0 auto;">
    <h2 style="color: #0d6efd; margin-bottom: 4px;"><?= Yii::t('app', 'Workspace invitation') ?></h2>
    <p style="color: #6b7280; margin-top: 0;">
        <?= Yii::t('app', '{inviter} invited you to join the "{workspace}" workspace in {appName} as {role}.', [
            'inviter' => Html::encode($inviterName),
            'workspace' => Html::encode($workspace->name),
            'appName' => Html::encode(Yii::$app->name),
            'role' => Html::encode($role),
        ]) ?>
    </p>

    <?php if (!$isExistingUser): ?>
        <p><?= Yii::t('app', 'Create an account with this email address, then click the button below to join.') ?></p>
    <?php endif; ?>

    <p style="margin: 24px 0;">
        <a href="<?= $acceptUrl ?>" style="display: inline-block; background: #0d6efd; color: #ffffff; text-decoration: none; padding: 12px 22px; border-radius: 6px;">
            <?= Yii::t('app', 'Accept invitation') ?>
        </a>
    </p>

    <p style="color: #9ca3af; font-size: 12px;">
        <?= Yii::t('app', 'If the button does not work, copy and paste this link:') ?><br>
        <a href="<?= $acceptUrl ?>"><?= Html::encode($acceptUrl) ?></a>
    </p>
</div>
