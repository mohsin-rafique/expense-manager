<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * Profile Model
 *
 * Represents the user profile with extended personal information,
 * avatar/banner image management, and Gravatar integration.
 *
 * @property int $user_id Primary key, foreign key to user table
 * @property string|null $name Full name of the user
 * @property string|null $designation Job title or role
 * @property string|null $phone Phone number
 * @property string|null $location City, country, or address
 * @property string|null $website Personal or professional website URL
 * @property string|null $timezone User's timezone (e.g., 'America/New_York')
 * @property string|null $bio Short biography or description
 * @property string|null $avatar Custom avatar filename (stored in uploads/avatars/)
 * @property string|null $banner Custom banner filename (stored in uploads/banners/)
 * @property string|null $gravatar_email Email used for Gravatar (fallback avatar)
 * @property string|null $gravatar_id MD5 hash of gravatar_email
 *
 * @property User $user The associated User model
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class Profile extends ActiveRecord
{
    /**
     * @var UploadedFile|null Temporary storage for avatar file upload
     */
    public $avatarFile;

    /**
     * @var UploadedFile|null Temporary storage for banner file upload
     */
    public $bannerFile;

    /**
     * Avatar image configuration
     */
    public const AVATAR_PATH = 'uploads/avatars/';
    public const AVATAR_MAX_SIZE = 2 * 1024 * 1024; // 2MB
    public const AVATAR_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    public const AVATAR_WIDTH = 300;
    public const AVATAR_HEIGHT = 300;

    /**
     * Banner image configuration
     */
    public const BANNER_PATH = 'uploads/banners/';
    public const BANNER_MAX_SIZE = 5 * 1024 * 1024; // 5MB
    public const BANNER_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    public const BANNER_WIDTH = 1200;
    public const BANNER_HEIGHT = 300;

    /**
     * {@inheritdoc}
     *
     * @return string The table name
     */
    public static function tableName(): string
    {
        return '{{%profile}}';
    }

    /**
     * {@inheritdoc}
     *
     * Defines validation rules for profile attributes including:
     * - Required fields
     * - String length limits
     * - File upload validation for avatar and banner
     * - URL format validation for website
     * - Email format validation for gravatar_email
     *
     * @return array Validation rules
     */
    public function rules(): array
    {
        return [
            // Required
            [['user_id'], 'required'],

            // Integer fields
            [['user_id'], 'integer'],

            // Text fields
            [['bio'], 'string'],

            // String length validation
            [['name', 'designation', 'phone', 'location', 'website', 'timezone', 'gravatar_email'], 'string', 'max' => 191],
            [['gravatar_id'], 'string', 'max' => 32],
            [['avatar', 'banner'], 'string', 'max' => 191],

            // Unique constraint
            [['user_id'], 'unique'],

            // Foreign key validation
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],

            // URL validation
            [['website'], 'url', 'defaultScheme' => 'https', 'skipOnEmpty' => true],

            // Email validation for Gravatar
            [['gravatar_email'], 'email', 'skipOnEmpty' => true],

            // Avatar file validation
            [
                ['avatarFile'],
                'file',
                'skipOnEmpty' => true,
                'extensions' => self::AVATAR_EXTENSIONS,
                'maxSize' => self::AVATAR_MAX_SIZE,
                'mimeTypes' => ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
                'wrongExtension' => Yii::t('app', 'Only {extensions} files are allowed for avatar.'),
                'tooBig' => Yii::t('app', 'Avatar file size cannot exceed {formattedLimit}.'),
            ],

            // Banner file validation
            [
                ['bannerFile'],
                'file',
                'skipOnEmpty' => true,
                'extensions' => self::BANNER_EXTENSIONS,
                'maxSize' => self::BANNER_MAX_SIZE,
                'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                'wrongExtension' => Yii::t('app', 'Only {extensions} files are allowed for banner.'),
                'tooBig' => Yii::t('app', 'Banner file size cannot exceed {formattedLimit}.'),
            ],

            // Trim whitespace
            [['name', 'designation', 'location', 'website', 'gravatar_email'], 'trim'],

            // Default values
            [['name', 'designation', 'phone', 'location', 'website', 'timezone', 'bio', 'avatar', 'banner', 'gravatar_email', 'gravatar_id'], 'default', 'value' => null],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array Attribute labels for form fields
     */
    public function attributeLabels(): array
    {
        return [
            'user_id' => Yii::t('app', 'User ID'),
            'name' => Yii::t('app', 'Full Name'),
            'designation' => Yii::t('app', 'Designation'),
            'phone' => Yii::t('app', 'Phone'),
            'location' => Yii::t('app', 'Location'),
            'website' => Yii::t('app', 'Website'),
            'timezone' => Yii::t('app', 'Timezone'),
            'bio' => Yii::t('app', 'Bio'),
            'avatar' => Yii::t('app', 'Avatar'),
            'avatarFile' => Yii::t('app', 'Profile Picture'),
            'banner' => Yii::t('app', 'Banner'),
            'bannerFile' => Yii::t('app', 'Cover Image'),
            'gravatar_email' => Yii::t('app', 'Gravatar Email'),
            'gravatar_id' => Yii::t('app', 'Gravatar ID'),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Before saving, generate gravatar_id from gravatar_email if provided.
     *
     * @param bool $insert Whether this is an insert operation
     * @return bool Whether the record should be saved
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Generate Gravatar ID from email
        if ($this->isAttributeChanged('gravatar_email')) {
            $this->gravatar_id = $this->gravatar_email
                ? md5(strtolower(trim($this->gravatar_email)))
                : null;
        }

        return true;
    }

    /**
     * Gets the associated User model
     *
     * @return \yii\db\ActiveQuery The User relation
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Avatar Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Uploads and saves the avatar image
     *
     * Handles the complete avatar upload process:
     * 1. Validates the uploaded file
     * 2. Creates upload directory if needed
     * 3. Generates unique filename
     * 4. Resizes image to configured dimensions
     * 5. Saves file and updates model
     * 6. Deletes old avatar if exists
     *
     * @return bool Whether the upload was successful
     */
    public function uploadAvatar(): bool
    {
        if (!$this->avatarFile instanceof UploadedFile) {
            return false;
        }

        if (!$this->validate(['avatarFile'])) {
            return false;
        }

        // Create upload directory
        $uploadPath = Yii::getAlias('@webroot/' . self::AVATAR_PATH);
        if (!FileHelper::createDirectory($uploadPath)) {
            Yii::error('Failed to create avatar upload directory: ' . $uploadPath, __METHOD__);
            return false;
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($this->avatarFile->extension);
        $filePath = $uploadPath . $filename;

        // Delete old avatar before saving new one
        $oldAvatar = $this->avatar;

        try {
            // Save and resize image
            Image::thumbnail(
                $this->avatarFile->tempName,
                self::AVATAR_WIDTH,
                self::AVATAR_HEIGHT
            )->save($filePath, ['quality' => 90]);

            // Update model
            $this->avatar = $filename;

            if ($this->save(false, ['avatar'])) {
                // Delete old avatar file
                $this->deleteAvatarFile($oldAvatar);
                return true;
            }
        } catch (\Exception $e) {
            Yii::error('Avatar upload failed: ' . $e->getMessage(), __METHOD__);
        }

        return false;
    }

    /**
     * Deletes the current avatar image
     *
     * Removes the avatar file from disk and clears the database field.
     *
     * @return bool Whether the deletion was successful
     */
    public function deleteAvatar(): bool
    {
        if (empty($this->avatar)) {
            return true;
        }

        $oldAvatar = $this->avatar;
        $this->avatar = null;

        if ($this->save(false, ['avatar'])) {
            $this->deleteAvatarFile($oldAvatar);
            return true;
        }

        return false;
    }

    /**
     * Deletes an avatar file from disk
     *
     * @param string|null $filename The avatar filename to delete
     * @return bool Whether the file was deleted
     */
    protected function deleteAvatarFile(?string $filename): bool
    {
        if (empty($filename)) {
            return true;
        }

        $filePath = Yii::getAlias('@webroot/' . self::AVATAR_PATH . $filename);

        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return true;
    }

    /**
     * Gets the avatar URL
     *
     * Returns the custom avatar URL if uploaded, otherwise falls back
     * to Gravatar. If neither is available, returns null.
     *
     * @param int $size The desired avatar size in pixels
     * @return string|null The avatar URL or null if not available
     */
    public function getAvatarUrl(int $size = 200): ?string
    {
        // Custom avatar takes priority
        if (!empty($this->avatar)) {
            $filePath = Yii::getAlias('@webroot/' . self::AVATAR_PATH . $this->avatar);
            if (file_exists($filePath)) {
                return Yii::getAlias('@web/' . self::AVATAR_PATH . $this->avatar);
            }
        }

        // Fall back to Gravatar
        if (!empty($this->gravatar_id)) {
            return '//gravatar.com/avatar/' . $this->gravatar_id . '?s=' . $size . '&d=mp';
        }

        return null;
    }

    /**
     * Checks if user has a custom avatar
     *
     * @return bool Whether a custom avatar is set
     */
    public function hasCustomAvatar(): bool
    {
        if (empty($this->avatar)) {
            return false;
        }

        $filePath = Yii::getAlias('@webroot/' . self::AVATAR_PATH . $this->avatar);
        return file_exists($filePath);
    }

    /*
    |--------------------------------------------------------------------------
    | Banner Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Uploads and saves the banner image
     *
     * Handles the complete banner upload process:
     * 1. Validates the uploaded file
     * 2. Creates upload directory if needed
     * 3. Generates unique filename
     * 4. Resizes image to configured dimensions
     * 5. Saves file and updates model
     * 6. Deletes old banner if exists
     *
     * @return bool Whether the upload was successful
     */
    public function uploadBanner(): bool
    {
        if (!$this->bannerFile instanceof UploadedFile) {
            return false;
        }

        if (!$this->validate(['bannerFile'])) {
            return false;
        }

        // Create upload directory
        $uploadPath = Yii::getAlias('@webroot/' . self::BANNER_PATH);
        if (!FileHelper::createDirectory($uploadPath)) {
            Yii::error('Failed to create banner upload directory: ' . $uploadPath, __METHOD__);
            return false;
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($this->bannerFile->extension);
        $filePath = $uploadPath . $filename;

        // Delete old banner before saving new one
        $oldBanner = $this->banner;

        try {
            // Save and resize image (crop to fit banner dimensions)
            Image::thumbnail(
                $this->bannerFile->tempName,
                self::BANNER_WIDTH,
                self::BANNER_HEIGHT,
                \Imagine\Image\ManipulatorInterface::THUMBNAIL_OUTBOUND
            )->save($filePath, ['quality' => 85]);

            // Update model
            $this->banner = $filename;

            if ($this->save(false, ['banner'])) {
                // Delete old banner file
                $this->deleteBannerFile($oldBanner);
                return true;
            }
        } catch (\Exception $e) {
            Yii::error('Banner upload failed: ' . $e->getMessage(), __METHOD__);
        }

        return false;
    }

    /**
     * Deletes the current banner image
     *
     * Removes the banner file from disk and clears the database field.
     *
     * @return bool Whether the deletion was successful
     */
    public function deleteBanner(): bool
    {
        if (empty($this->banner)) {
            return true;
        }

        $oldBanner = $this->banner;
        $this->banner = null;

        if ($this->save(false, ['banner'])) {
            $this->deleteBannerFile($oldBanner);
            return true;
        }

        return false;
    }

    /**
     * Deletes a banner file from disk
     *
     * @param string|null $filename The banner filename to delete
     * @return bool Whether the file was deleted
     */
    protected function deleteBannerFile(?string $filename): bool
    {
        if (empty($filename)) {
            return true;
        }

        $filePath = Yii::getAlias('@webroot/' . self::BANNER_PATH . $filename);

        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return true;
    }

    /**
     * Gets the banner URL
     *
     * Returns the custom banner URL if uploaded, otherwise returns null.
     *
     * @return string|null The banner URL or null if not set
     */
    public function getBannerUrl(): ?string
    {
        if (!empty($this->banner)) {
            $filePath = Yii::getAlias('@webroot/' . self::BANNER_PATH . $this->banner);
            if (file_exists($filePath)) {
                return Yii::getAlias('@web/' . self::BANNER_PATH . $this->banner);
            }
        }

        return null;
    }

    /**
     * Checks if user has a custom banner
     *
     * @return bool Whether a custom banner is set
     */
    public function hasCustomBanner(): bool
    {
        if (empty($this->banner)) {
            return false;
        }

        $filePath = Yii::getAlias('@webroot/' . self::BANNER_PATH . $this->banner);
        return file_exists($filePath);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Generates a unique filename for uploaded files
     *
     * Creates a filename using user ID and random string to ensure
     * uniqueness and prevent conflicts.
     *
     * @param string $extension The file extension
     * @return string The unique filename
     */
    protected function generateUniqueFilename(string $extension): string
    {
        $timestamp = time();
        $random = Yii::$app->security->generateRandomString(8);

        return "{$this->user_id}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Gets the display name for the user
     *
     * Returns the profile name if set, otherwise returns the username.
     *
     * @return string The display name
     */
    public function getDisplayName(): string
    {
        if (!empty($this->name)) {
            return $this->name;
        }

        return $this->user ? $this->user->username : 'User';
    }

    /**
     * Gets the initials for avatar placeholder
     *
     * Extracts initials from name or username for use when
     * no avatar image is available.
     *
     * @return string The initials (1-2 characters)
     */
    public function getInitials(): string
    {
        $name = $this->getDisplayName();

        // Split name into words
        $words = preg_split('/\s+/', trim($name));

        if (count($words) >= 2) {
            // First letter of first and last word
            return strtoupper(mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1));
        }

        // First two letters of single word
        return strtoupper(mb_substr($name, 0, 2));
    }
}
