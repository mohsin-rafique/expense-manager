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
 * SignupForm model for handling user registration
 *
 * This form model handles the new user registration process:
 * - Validates username uniqueness
 * - Validates email format and uniqueness
 * - Enforces password strength requirements
 * - Creates new user account with profile
 *
 * @property-read User|null $user The registered user instance
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class SignupForm extends Model
{
    /**
     * @var string The desired username
     */
    public $username;

    /**
     * @var string The user's email address
     */
    public $email;

    /**
     * @var string The desired password
     */
    public $password;

    /**
     * @var bool Agreement to terms and conditions
     */
    public $agreeTerms;

    /**
     * @var User|null The created user instance
     */
    private $_user;

    /**
     * {@inheritdoc}
     *
     * Defines validation rules for the signup form.
     *
     * @return array The validation rules
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['username', 'email', 'password'], 'required'],

            // Username validation
            ['username', 'trim'],
            ['username', 'string', 'min' => 3, 'max' => 255],
            [
                'username',
                'match',
                'pattern' => '/^[a-zA-Z0-9_]+$/',
                'message' => Yii::t('app', 'Username can only contain letters, numbers, and underscores.')
            ],
            [
                'username',
                'unique',
                'targetClass' => User::class,
                'message' => Yii::t('app', 'This username is already taken.')
            ],

            // Email validation
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            [
                'email',
                'unique',
                'targetClass' => User::class,
                'message' => Yii::t('app', 'This email address is already registered.')
            ],

            // Password validation
            [
                'password',
                'string',
                'min' => 6,
                'tooShort' => Yii::t('app', 'Password must be at least {min, number} characters.')
            ],

            // Terms agreement
            [
                'agreeTerms',
                'required',
                'requiredValue' => 1,
                'message' => Yii::t('app', 'You must agree to the Terms of Service.')
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Returns user-friendly attribute labels.
     *
     * @return array Attribute labels
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'agreeTerms' => Yii::t('app', 'I agree to the Terms of Service'),
        ];
    }

    /**
     * Registers a new user account
     *
     * Creates a new user with the provided credentials and
     * generates an associated profile record.
     *
     * @return bool True if registration was successful
     */
    public function signup()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $user->created_at = time();
        $user->updated_at = time();
        $user->registration_ip = Yii::$app->request->userIP;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$user->save()) {
                throw new \Exception('Failed to save user.');
            }

            // Create associated profile
            $profile = new Profile();
            $profile->user_id = $user->id;
            $profile->save(false);

            // Create default settings
            $settings = new Settings();
            $settings->user_id = $user->id;
            $settings->currency = 'USD';
            $settings->currency_position = 'before';
            $settings->thousand_separator = ',';
            $settings->decimal_separator = '.';
            $settings->decimal_places = 2;
            $settings->save(false);

            // Create the user's personal workspace (owner membership)
            if (Workspace::createPersonalFor($user) === null) {
                throw new \Exception('Failed to create personal workspace.');
            }

            // Attach any pending email invitations addressed to this user
            WorkspaceMember::updateAll(
                ['user_id' => $user->id, 'status' => WorkspaceMember::STATUS_ACTIVE],
                ['invited_email' => $user->email, 'user_id' => null, 'status' => WorkspaceMember::STATUS_PENDING]
            );

            $transaction->commit();
            $this->_user = $user;

            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Signup failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Returns the registered user instance
     *
     * @return User|null The user model or null if not registered yet
     */
    public function getUser()
    {
        return $this->_user;
    }
}
