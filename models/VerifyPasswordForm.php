<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use yii\base\InvalidArgumentException;
use yii\base\Model;

/**
 * VerifyPasswordForm handles the password reset confirmation step.
 *
 * Accepts a valid password reset token, validates the new password,
 * and persists the change to the user record.
 *
 * @package app\models
 */
class VerifyPasswordForm extends Model
{
    /** @var string|null The new password entered by the user */
    public ?string $password = null;

    /** @var User The user identified by the reset token */
    private User $_user;

    /**
     * Creates a form model given a valid password reset token.
     *
     * @param string $token Password reset token
     * @param array $config Name-value pairs for object property initialization
     * @throws InvalidArgumentException if the token is empty or invalid
     */
    public function __construct(string $token, array $config = [])
    {
        if (empty($token)) {
            throw new InvalidArgumentException('Password reset token cannot be blank.');
        }

        $this->_user = User::findByPasswordResetToken($token);

        if (!$this->_user) {
            throw new InvalidArgumentException('Wrong password reset token.');
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }

    /**
     * Resets the user's password.
     *
     * @return bool whether the password was reset and saved successfully
     */
    public function resetPassword(): bool
    {
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();

        return $user->save(false);
    }
}
