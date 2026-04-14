<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ChangePasswordForm model for handling password change requests
 *
 * This form model handles the secure password change process for
 * authenticated users. It validates the current password and ensures
 * the new password meets security requirements.
 *
 * Security Features:
 * - Validates current password before allowing change
 * - Enforces minimum password length
 * - Requires password confirmation to prevent typos
 *
 * Usage example:
 * ```php
 * $model = new ChangePasswordForm();
 * if ($model->load(Yii::$app->request->post()) && $model->validate()) {
 *     $user = Yii::$app->user->identity;
 *     if ($user->validatePassword($model->oldPassword)) {
 *         $user->setPassword($model->newPassword);
 *         $user->save();
 *     }
 * }
 * ```
 *
 * @property string $oldPassword The user's current password
 * @property string $newPassword The desired new password
 * @property string $confirmPassword Confirmation of the new password
 *
 * @see User::setPassword()
 * @see User::validatePassword()
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ChangePasswordForm extends Model
{
    /**
     * @var string The user's current password for verification
     */
    public $oldPassword;

    /**
     * @var string The new password to set
     */
    public $newPassword;

    /**
     * @var string Confirmation of the new password (must match newPassword)
     */
    public $confirmPassword;

    /**
     * {@inheritdoc}
     *
     * Defines validation rules for the password change form.
     *
     * Rules:
     * - All fields are required
     * - New password must be at least 6 characters
     * - Confirm password must match new password
     *
     * @return array The validation rules
     */
    public function rules(): array
    {
        return [
            // All fields are required
            [['oldPassword', 'newPassword', 'confirmPassword'], 'required'],

            // New password must meet minimum length requirement
            ['newPassword', 'string', 'min' => 6, 'tooShort' => Yii::t('app', 'Password must be at least {min, number} characters.')],

            // Confirm password must match new password
            [
                'confirmPassword',
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => Yii::t('app', 'Passwords do not match.'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Returns user-friendly attribute labels for form fields.
     *
     * @return array Attribute labels
     */
    public function attributeLabels()
    {
        return [
            'oldPassword' => Yii::t('app', 'Current Password'),
            'newPassword' => Yii::t('app', 'New Password'),
            'confirmPassword' => Yii::t('app', 'Confirm New Password'),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Returns hint text for form fields.
     *
     * @return array Attribute hints
     */
    public function attributeHints()
    {
        return [
            'oldPassword' => Yii::t('app', 'Enter your current password to verify your identity.'),
            'newPassword' => Yii::t('app', 'Minimum 6 characters. Use a mix of letters, numbers, and symbols for better security.'),
            'confirmPassword' => Yii::t('app', 'Re-enter your new password to confirm.'),
        ];
    }

    /**
     * Validates the old (current) password against the user's stored password
     *
     * This method should be called in the controller after form validation
     * to verify that the user knows their current password before allowing
     * them to set a new one.
     *
     * Usage example:
     * ```php
     * if ($model->validate() && $model->validateOldPassword()) {
     *     // Proceed with password change
     * }
     * ```
     *
     * @return bool True if the old password is correct, false otherwise
     */
    public function validateOldPassword()
    {
        if ($this->hasErrors()) {
            return false;
        }

        /** @var User $user */
        $user = Yii::$app->user->identity;

        if (!$user || !$user->validatePassword($this->oldPassword)) {
            $this->addError('oldPassword', Yii::t('app', 'Current password is incorrect.'));
            return false;
        }

        return true;
    }

    /**
     * Changes the password for the currently authenticated user
     *
     * This method validates the form, verifies the old password,
     * and updates the user's password in a single operation.
     *
     * Usage example:
     * ```php
     * $model = new ChangePasswordForm();
     * if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
     *     Yii::$app->session->setFlash('success', 'Password changed successfully.');
     * }
     * ```
     *
     * @return bool True if password was changed successfully, false otherwise
     */
    public function changePassword()
    {
        if (!$this->validate()) {
            return false;
        }

        if (!$this->validateOldPassword()) {
            return false;
        }

        /** @var User $user */
        $user = Yii::$app->user->identity;
        $user->setPassword($this->newPassword);

        return $user->save(false);
    }
}
