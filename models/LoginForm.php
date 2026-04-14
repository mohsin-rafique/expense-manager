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
 * LoginForm is the model behind the login form.
 *
 * Handles user authentication with:
 * - Username/email and password validation
 * - Remember me functionality
 * - Last login timestamp tracking
 *
 * @property-read User|null $user The user model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class LoginForm extends Model
{
    /**
     * @var string Username or email
     */
    public $username;

    /**
     * @var string Password
     */
    public $password;

    /**
     * @var bool Whether to remember the user
     */
    public $rememberMe = true;

    /**
     * @var User|null Cached user instance
     */
    private $_user;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'rememberMe' => Yii::t('app', 'Remember Me'),
        ];
    }

    /**
     * Validates the password.
     *
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, Yii::t('app', 'Incorrect username or password.'));
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * Updates the last_login_at timestamp on successful login.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login(): bool
    {
        if ($this->validate()) {
            $user = $this->getUser();

            // Calculate remember me duration (30 days or session only)
            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0;

            // Attempt to log in the user
            if (Yii::$app->user->login($user, $duration)) {
                // Update last login timestamp
                $this->updateLastLogin($user);

                return true;
            }
        }

        return false;
    }

    /**
     * Updates the last login timestamp for the user.
     *
     * Uses updateAll() to avoid triggering TimestampBehavior
     * which would update the updated_at field.
     *
     * @param User $user The user model
     */
    protected function updateLastLogin(User $user): void
    {
        // Method 1: Using updateAll() - doesn't trigger behaviors or events
        User::updateAll(
            ['last_login_at' => time()],
            ['id' => $user->id]
        );

        // Update the cached user instance as well
        $user->last_login_at = time();
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
