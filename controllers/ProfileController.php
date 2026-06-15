<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\User;
use app\models\Profile;
use app\models\Settings;
use app\models\ChangePasswordForm;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use FilesystemIterator;

/**
 * ProfileController - Handles user profile and account settings management
 *
 * This controller provides:
 * - User profile display and editing
 * - Avatar and banner image upload
 * - Password change functionality
 * - Currency and localization settings
 * - Database backup and export features
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ProfileController extends Controller
{
    /**
     * {@inheritdoc}
     *
     * Configures access control and verb filters for all profile-related actions.
     * All actions require authenticated users.
     *
     * @return array The behavior configurations
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => [
                            'index',
                            'settings',
                            'upload-avatar',
                            'delete-avatar',
                            'upload-banner',
                            'delete-banner',
                            'currency-settings',
                            'change-password',
                            'database-backup',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'upload-avatar' => ['POST'],
                    'delete-avatar' => ['POST'],
                    'upload-banner' => ['POST'],
                    'delete-banner' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Displays the user's profile page
     *
     * Shows comprehensive profile information including:
     * - Personal details (name, email, phone, etc.)
     * - Account statistics (expenses, income summary)
     * - Quick action links to settings
     *
     * @return string The rendered profile view
     */
    public function actionIndex(): string
    {
        $user = User::findOne(Yii::$app->user->identity->id);

        return $this->render('index', [
            'user' => $user,
        ]);
    }

    /**
     * Displays and handles the comprehensive settings page
     *
     * This action manages multiple settings sections:
     * - Personal Details: Name, email, phone, location, bio, etc.
     * - Change Password: Secure password update with validation
     * - Currency Settings: Currency format, separators, decimal places
     * - Backups: Database export functionality and backup history
     *
     * The active tab is determined by the 'tab' query parameter.
     *
     * @return string|Response The rendered settings view or redirect response
     */
    public function actionSettings()
    {
        // Initialize active tab from query parameter
        $activeTab = Yii::$app->request->get('tab', 'personalDetails');

        // Fetch the current user and profile models
        $userModel = User::findOne(Yii::$app->user->identity->id);
        $profileModel = $userModel->profile;

        // Handle profile form submission
        if ($this->processProfileForm($profileModel, $userModel)) {
            return $this->redirect(['settings', 'tab' => 'personalDetails']);
        }

        // Handle password change form submission
        $changePasswordModel = new ChangePasswordForm();
        if ($this->processPasswordChangeForm($changePasswordModel)) {
            return $this->redirect(['settings', 'tab' => 'changePassword']);
        }

        // Handle currency settings form submission
        $currencySettingsModel = $this->findModelSettings(Yii::$app->user->identity->id);
        if ($this->processCurrencySettingsForm($currencySettingsModel)) {
            return $this->redirect(['settings', 'tab' => 'currencySettings']);
        }

        // Handle database export form submission
        if ($this->processDatabaseExport()) {
            return $this->redirect(['settings', 'tab' => 'backups']);
        }

        // Get list of backup files
        $files = $this->getBackupFiles();

        return $this->render('settings', [
            'activeTab' => $activeTab,
            'userModel' => $userModel,
            'changePasswordModel' => $changePasswordModel,
            'profileModel' => $profileModel,
            'currencySettingsModel' => $currencySettingsModel,
            'files' => $files,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Avatar Upload Actions
    |--------------------------------------------------------------------------
    */

    /**
     * Handles avatar image upload via AJAX
     *
     * Accepts a POST request with an avatar image file, validates it,
     * resizes it to the configured dimensions, and saves it.
     *
     * @return Response JSON response with success status and avatar URL
     */
    public function actionUploadAvatar(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $profile = $this->findModel(Yii::$app->user->identity->id);
        $profile->avatarFile = UploadedFile::getInstanceByName('avatarFile');

        if (!$profile->avatarFile) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'No file was uploaded.')));
        }

        if ($profile->uploadAvatar()) {
            return $this->asJson(ApiResponse::success(
                Yii::t('app', 'Avatar uploaded successfully.'),
                ['avatarUrl' => $profile->getAvatarUrl(200)]
            ));
        }

        $errors = $profile->getErrors('avatarFile');
        $errorMessage = !empty($errors) ? reset($errors) : Yii::t('app', 'Failed to upload avatar.');

        return $this->asJson(ApiResponse::error($errorMessage));
    }

    /**
     * Handles avatar deletion via AJAX
     *
     * Removes the custom avatar and reverts to Gravatar or default.
     *
     * @return Response JSON response with success status
     */
    public function actionDeleteAvatar(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $profile = $this->findModel(Yii::$app->user->identity->id);

        if ($profile->deleteAvatar()) {
            return $this->asJson(ApiResponse::success(
                Yii::t('app', 'Avatar deleted successfully.'),
                ['avatarUrl' => $profile->getAvatarUrl(200)]
            ));
        }

        return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to delete avatar.')));
    }

    /*
    |--------------------------------------------------------------------------
    | Banner Upload Actions
    |--------------------------------------------------------------------------
    */

    /**
     * Handles banner image upload via AJAX
     *
     * Accepts a POST request with a banner image file, validates it,
     * resizes it to the configured dimensions, and saves it.
     *
     * @return Response JSON response with success status and banner URL
     */
    public function actionUploadBanner(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $profile = $this->findModel(Yii::$app->user->identity->id);
        $profile->bannerFile = UploadedFile::getInstanceByName('bannerFile');

        if (!$profile->bannerFile) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'No file was uploaded.')));
        }

        if ($profile->uploadBanner()) {
            return $this->asJson(ApiResponse::success(
                Yii::t('app', 'Banner uploaded successfully.'),
                ['bannerUrl' => $profile->getBannerUrl()]
            ));
        }

        $errors = $profile->getErrors('bannerFile');
        $errorMessage = !empty($errors) ? reset($errors) : Yii::t('app', 'Failed to upload banner.');

        return $this->asJson(ApiResponse::error($errorMessage));
    }

    /**
     * Handles banner deletion via AJAX
     *
     * Removes the custom banner and reverts to default gradient.
     *
     * @return Response JSON response with success status
     */
    public function actionDeleteBanner(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $profile = $this->findModel(Yii::$app->user->identity->id);

        if ($profile->deleteBanner()) {
            return $this->asJson(ApiResponse::success(Yii::t('app', 'Banner deleted successfully.')));
        }

        return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to delete banner.')));
    }

    /*
    |--------------------------------------------------------------------------
    | Form Processing Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Processes the profile update form submission
     *
     * Validates and saves profile changes including:
     * - Name, designation, phone, location
     * - Website, timezone, bio
     * - Gravatar email for avatar
     *
     * @param Profile $profileModel The profile model to update
     * @param User $userModel The associated user model
     * @return bool True if form was processed and saved successfully
     */
    protected function processProfileForm(Profile $profileModel, User $userModel): bool
    {
        if (!$profileModel->load(Yii::$app->request->post())) {
            return false;
        }

        $isValid = $userModel->validate();
        $isValid = $profileModel->validate() && $isValid;

        if ($isValid && $profileModel->save(false)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Profile updated successfully.'));
            return true;
        }

        return false;
    }

    /**
     * Processes the password change form submission
     *
     * Validates the old password and updates to the new password.
     * Includes security checks:
     * - Validates current password before allowing change
     * - Ensures new password meets security requirements
     *
     * @param ChangePasswordForm $changePasswordModel The password change form model
     * @return bool True if password was changed successfully
     */
    protected function processPasswordChangeForm(ChangePasswordForm $changePasswordModel): bool
    {
        if (!$changePasswordModel->load(Yii::$app->request->post()) || !$changePasswordModel->validate()) {
            return false;
        }

        $user = Yii::$app->user->identity;

        if (!$user instanceof User) {
            return false;
        }

        if ($user->validatePassword($changePasswordModel->oldPassword)) {
            $user->setPassword($changePasswordModel->newPassword);
            $user->save();
            Yii::$app->session->setFlash('success', Yii::t('app', 'Password changed successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Old password is incorrect.'));
        }

        return true;
    }

    /**
     * Processes the currency settings form submission
     *
     * Updates currency display preferences:
     * - Currency code (USD, EUR, GBP, etc.)
     * - Symbol position (before/after amount)
     * - Thousand and decimal separators
     * - Number of decimal places
     *
     * @param Settings $currencySettingsModel The currency settings model
     * @return bool True if settings were saved successfully
     */
    protected function processCurrencySettingsForm(Settings $currencySettingsModel): bool
    {
        if (!$currencySettingsModel->load(Yii::$app->request->post())) {
            return false;
        }

        if ($currencySettingsModel->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Currency settings updated successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to update currency settings.'));
        }

        return true;
    }

    /**
     * Processes the database export form submission
     *
     * Creates a complete SQL backup containing:
     * - expenses: All expense records
     * - expense_categories: Expense category definitions
     * - incomes: All income records
     * - income_categories: Income category definitions
     * - settings: User settings and preferences
     * - user: User account data
     *
     * The backup file is saved with a unique hash-based filename
     * in the sql-exports directory.
     *
     * @return bool True if export was initiated via POST
     */
    protected function processDatabaseExport(): bool
    {
        if (!Yii::$app->request->isPost || Yii::$app->request->post('form-type') !== 'export-database') {
            return false;
        }

        $directory = Yii::getAlias('@app/web/sql-exports/');

        // Ensure directory exists with proper permissions
        if (!$this->ensureDirectoryExists($directory)) {
            return true;
        }

        // Generate unique filename
        $sqlFileName = $this->generateBackupFilename();
        $sqlFilePath = $directory . $sqlFileName;

        // Perform the export
        if ($this->exportDatabaseToFile($sqlFilePath)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Database export completed successfully.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to export database.'));
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Database Export Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Ensures the backup directory exists with proper permissions
     *
     * Creates the directory if it doesn't exist and sets
     * permissions to allow file writing.
     *
     * @param string $directory The directory path to ensure
     * @return bool True if directory exists or was created successfully
     */
    protected function ensureDirectoryExists(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        if (!mkdir($directory, 0777, true)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to create the backup directory.'));
            return false;
        }

        chmod($directory, 0777);
        return true;
    }

    /**
     * Generates a unique filename for the database backup
     *
     * Creates a filename using a truncated SHA-256 hash with hyphens
     * for readability. Format: xxxx-xxxx-xxxx-xxxx.sql
     *
     * @return string The generated filename
     */
    protected function generateBackupFilename(): string
    {
        $randomString = Yii::$app->security->generateRandomString();
        $hash = hash('sha256', $randomString);
        $shortHash = substr($hash, 0, 16);
        $shortHashWithHyphens = implode('-', str_split($shortHash, 4));

        return "{$shortHashWithHyphens}.sql";
    }

    /**
     * Exports the database tables to an SQL file
     *
     * Generates both CREATE TABLE statements and INSERT statements
     * for all data in the specified tables.
     *
     * @param string $filePath The full path to the output file
     * @return bool True if export was successful
     */
    protected function exportDatabaseToFile(string $filePath): bool
    {
        $db = Yii::$app->db;

        // Tables to include in the backup
        $tables = [
            'expenses',
            'expense_categories',
            'incomes',
            'income_categories',
            'profile',
            'settings',
            'user',
        ];

        // Ensure directory exists
        FileHelper::createDirectory(dirname($filePath));

        $file = fopen($filePath, 'w');
        if (!$file) {
            return false;
        }

        // Write header comment
        $this->writeBackupHeader($file);

        foreach ($tables as $table) {
            // Write table structure
            $createTableSQL = $this->generateCreateTableSQL($db, $table);
            fwrite($file, "\n-- Table structure for `{$table}`\n");
            fwrite($file, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($file, $createTableSQL . ";\n\n");

            // Write table data
            fwrite($file, "-- Data for `{$table}`\n");
            $data = $db->createCommand("SELECT * FROM {$table}")->queryAll();
            foreach ($data as $row) {
                $insertSQL = $this->generateInsertSQL($table, $row);
                fwrite($file, $insertSQL . ";\n");
            }
            fwrite($file, "\n");
        }

        fclose($file);
        return true;
    }

    /**
     * Writes the backup file header with metadata
     *
     * Includes generation timestamp, application info,
     * and MySQL compatibility settings.
     *
     * @param resource $file The file handle to write to
     */
    protected function writeBackupHeader($file): void
    {
        $header = <<<SQL
-- ============================================================
-- Expense Manager Database Backup
-- Generated: %s
-- Application: Expense Manager
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

SQL;
        fwrite($file, sprintf($header, date('Y-m-d H:i:s')));
    }

    /**
     * Generates the CREATE TABLE SQL statement for a table
     *
     * Uses MySQL's SHOW CREATE TABLE command to get the exact
     * table structure including indexes and constraints.
     *
     * @param \yii\db\Connection $db The database connection
     * @param string $table The table name
     * @return string The CREATE TABLE SQL statement
     */
    protected function generateCreateTableSQL($db, string $table): string
    {
        $command = $db->createCommand("SHOW CREATE TABLE {$table}");
        $row = $command->queryOne();

        return $row['Create Table'];
    }

    /**
     * Generates an INSERT SQL statement for a single row
     *
     * Properly escapes all values to prevent SQL injection
     * and handle special characters.
     *
     * @param string $table The table name
     * @param array $row The row data as associative array
     * @return string The INSERT SQL statement
     */
    protected function generateInsertSQL(string $table, array $row): string
    {
        $columns = implode('`, `', array_keys($row));
        $values = implode(', ', array_map(function ($value) {
            if ($value === null) {
                return 'NULL';
            }
            return Yii::$app->db->quoteValue($value);
        }, $row));

        return "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$values})";
    }

    /**
     * Retrieves the list of backup files from the exports directory
     *
     * Scans the sql-exports directory for .sql files and returns
     * them sorted by modification time (newest first).
     *
     * @return array List of backup files with name and modified timestamp
     */
    protected function getBackupFiles(): array
    {
        $directory = Yii::getAlias('@app/web/sql-exports/');
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        $fileInfos = [];

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->getExtension() === 'sql') {
                $fileInfos[] = [
                    'name' => $fileInfo->getFilename(),
                    'modified' => $fileInfo->getMTime(),
                    'size' => $fileInfo->getSize(),
                ];
            }
        }

        // Sort by modification time descending (newest first)
        usort($fileInfos, function ($a, $b) {
            return $b['modified'] <=> $a['modified'];
        });

        return $fileInfos;
    }

    /*
    |--------------------------------------------------------------------------
    | Model Finders
    |--------------------------------------------------------------------------
    */

    /**
     * Finds the Profile model based on user ID
     *
     * @param int $id The user ID
     * @return Profile The loaded profile model
     * @throws NotFoundHttpException if the profile cannot be found
     */
    protected function findModel(int $id): Profile
    {
        $model = Profile::findOne(['user_id' => $id]);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested profile does not exist.'));
    }

    /**
     * Finds the Settings model based on user ID
     *
     * @param int $id The user ID
     * @return Settings The loaded settings model
     * @throws NotFoundHttpException if the settings cannot be found
     */
    protected function findModelSettings(int $id): Settings
    {
        $model = Settings::findOne(['user_id' => $id]);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested settings do not exist.'));
    }
}
