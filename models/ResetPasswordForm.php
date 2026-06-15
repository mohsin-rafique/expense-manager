<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ResetPasswordForm handles the password reset request flow.
 *
 * Validates the submitted email address and sends a password reset
 * link to the user if the account exists and is active.
 *
 * @package app\models
 */
class ResetPasswordForm extends Model
{
    /** @var string|null The email address submitted by the user */
    public ?string $email = null;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            [
                'email',
                'exist',
                'targetClass' => User::class,
                'filter' => ['status' => User::STATUS_ACTIVE],
                'message' => 'Couldn\'t find your Account',
            ],
        ];
    }

    /**
     * Sends an email with a password reset link.
     *
     * @return string|false The password reset token on success, false on failure
     */
    public function sendEmail(): string|false
    {
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $this->email,
        ]);

        if (!$user) {
            return false;
        }

        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return false;
            }
        }

        $sent = Yii::$app
            ->mailer
            ->compose(
                ['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Password reset for ' . Yii::$app->name)
            ->send();

        return $sent ? $user->password_reset_token : false;
    }
}
