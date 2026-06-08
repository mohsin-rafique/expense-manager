<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Workspace invitation email (plain text).
 *
 * @var yii\web\View $this
 * @var app\models\Workspace $workspace
 * @var app\models\User|null $inviter
 * @var string $acceptUrl
 * @var bool $isExistingUser
 * @var string $role
 */

$inviterName = $inviter->username ?? Yii::$app->name;

echo Yii::t('app', 'Workspace invitation') . "\n\n";
echo Yii::t('app', '{inviter} invited you to join the "{workspace}" workspace in {appName} as {role}.', [
    'inviter' => $inviterName,
    'workspace' => $workspace->name,
    'appName' => Yii::$app->name,
    'role' => $role,
]) . "\n\n";

if (!$isExistingUser) {
    echo Yii::t('app', 'Create an account with this email address, then open the link below to join.') . "\n\n";
}

echo Yii::t('app', 'Accept invitation') . ': ' . $acceptUrl . "\n";
