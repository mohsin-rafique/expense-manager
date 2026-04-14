<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%incomes}}".
 *
 * Represents income records with support for categorization, file attachments,
 * and comprehensive financial tracking.
 *
 * @property int $id
 * @property int $user_id
 * @property int $income_category_id
 * @property string $entry_date
 * @property string|null $reference
 * @property string|null $description
 * @property string $amount
 * @property string|null $filename
 * @property string|null $filepath
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property IncomeCategory $incomeCategory
 * @property User $createdBy
 * @property User $updatedBy
 * @property User $user
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class Income extends ActiveRecord
{
    /**
     * @var \yii\web\UploadedFile File upload instance
     */
    public $myFile;

    /**
     * Allowed file extensions for attachments
     */
    public const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'pdf'];

    /**
     * Maximum file size in bytes (1MB)
     */
    public const MAX_FILE_SIZE = 1048576;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%incomes}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        // Clean amount format before validation
        if (!empty($this->amount) && is_string($this->amount)) {
            $this->amount = str_replace(',', '', trim($this->amount));
        }

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['user_id', 'income_category_id', 'entry_date', 'amount'], 'required'],

            // Integer fields
            [['user_id', 'income_category_id', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],

            // Date validation
            [['entry_date'], 'safe'],
            [['entry_date'], 'date', 'format' => 'php:Y-m-d'],

            // String length limits
            [['reference'], 'string', 'max' => 191],
            [['description'], 'string'],
            [['filename'], 'string', 'max' => 96],
            [['filepath'], 'string', 'max' => 191],

            // File validation
            [
                ['myFile'],
                'file',
                'extensions' => self::ALLOWED_EXTENSIONS,
                'maxSize' => self::MAX_FILE_SIZE,
                'tooBig' => Yii::t('app', 'File size must be less than 1MB.'),
                'wrongExtension' => Yii::t('app', 'Only PNG, JPG, JPEG, and PDF files are allowed.'),
            ],

            // Foreign key validation
            [
                ['income_category_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => IncomeCategory::class,
                'targetAttribute' => ['income_category_id' => 'id'],
            ],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by' => 'id'],
            ],
            [
                ['updated_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['updated_by' => 'id'],
            ],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],

            // Trim whitespace
            [['reference', 'description'], 'filter', 'filter' => 'trim'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User'),
            'income_category_id' => Yii::t('app', 'Category'),
            'reference' => Yii::t('app', 'Reference'),
            'entry_date' => Yii::t('app', 'Date'),
            'description' => Yii::t('app', 'Description'),
            'amount' => Yii::t('app', 'Amount'),
            'myFile' => Yii::t('app', 'Attachment'),
            'filename' => Yii::t('app', 'File Name'),
            'filepath' => Yii::t('app', 'File Path'),
            'created_at' => Yii::t('app', 'Created'),
            'updated_at' => Yii::t('app', 'Updated'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    /**
     * Gets query for [[IncomeCategory]].
     *
     * @return ActiveQuery
     */
    public function getIncomeCategory(): ActiveQuery
    {
        return $this->hasOne(IncomeCategory::class, ['id' => 'income_category_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return ActiveQuery
     */
    public function getCreatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[UpdatedBy]].
     *
     * @return ActiveQuery
     */
    public function getUpdatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return ActiveQuery
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Get the full file path for the attachment
     *
     * @return string|null
     */
    public function getImageFile(): ?string
    {
        return isset($this->filepath) ? $this->filepath : null;
    }

    /**
     * Get the full URL for the attachment
     *
     * @return string|null
     */
    public function getFileUrl(): ?string
    {
        return isset($this->filepath) ? Yii::getAlias('@web/' . $this->filepath) : null;
    }

    /**
     * Get the absolute file path
     *
     * @return string|null
     */
    public function getAbsoluteFilePath(): ?string
    {
        return isset($this->filepath) ? Yii::getAlias('@webroot/' . $this->filepath) : null;
    }

    /**
     * Check if the income has an attachment
     *
     * @return bool
     */
    public function hasAttachment(): bool
    {
        return !empty($this->filename) && !empty($this->filepath);
    }

    /**
     * Get file extension
     *
     * @return string|null
     */
    public function getFileExtension(): ?string
    {
        if (!$this->hasAttachment()) {
            return null;
        }
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if attachment is an image
     *
     * @return bool
     */
    public function isImageAttachment(): bool
    {
        $ext = $this->getFileExtension();
        return in_array($ext, ['png', 'jpg', 'jpeg']);
    }

    /**
     * Check if attachment is a PDF
     *
     * @return bool
     */
    public function isPdfAttachment(): bool
    {
        return $this->getFileExtension() === 'pdf';
    }

    /**
     * Get file size in human-readable format
     *
     * @return string
     */
    public function getFileSizeFormatted(): string
    {
        $filePath = $this->getAbsoluteFilePath();
        if (!$filePath || !file_exists($filePath)) {
            return 'N/A';
        }

        $bytes = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get Bootstrap icon class for file type
     *
     * @return string
     */
    public function getFileIcon(): string
    {
        $ext = $this->getFileExtension();

        return match ($ext) {
            'pdf' => 'bi-file-pdf text-danger',
            'png', 'jpg', 'jpeg' => 'bi-file-image text-primary',
            default => 'bi-file-earmark text-secondary',
        };
    }

    /**
     * Delete the attached file from storage
     *
     * @return bool
     */
    public function deleteAttachment(): bool
    {
        $filePath = $this->getAbsoluteFilePath();

        if ($filePath && file_exists($filePath)) {
            return @unlink($filePath);
        }

        return true;
    }

    /**
     * Get formatted amount with currency
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        if (extension_loaded('intl')) {
            return Yii::$app->currency->format($this->amount);
        }

        return number_format((float) $this->amount, 2);
    }

    /**
     * Get formatted date
     *
     * @param string $format Date format (default: 'medium')
     * @return string
     */
    public function getFormattedDate(string $format = 'medium'): string
    {
        return Yii::$app->formatter->asDate($this->entry_date, $format);
    }

    /**
     * Calculate page total for GridView footer
     *
     * @param array $provider Data provider models
     * @param string $value Attribute name to sum
     * @return string Formatted total
     */
    public static function pageTotal(array $provider, string $value): string
    {
        $total = 0;
        foreach ($provider as $item) {
            $total += !empty($item[$value]) ? (float) $item[$value] : 0;
        }

        if (extension_loaded('intl')) {
            return Yii::$app->currency->format($total);
        }
        return number_format($total, 2);
    }

    /**
     * Get total income for a user within date range
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param string|null $startDate Start date (Y-m-d)
     * @param string|null $endDate End date (Y-m-d)
     * @param int|null $categoryId Filter by category
     * @return float
     */
    public static function getTotalIncome(
        ?int $userId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $categoryId = null
    ): float {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->where(['user_id' => $userId]);

        if ($startDate !== null) {
            $query->andWhere(['>=', 'entry_date', $startDate]);
        }

        if ($endDate !== null) {
            $query->andWhere(['<=', 'entry_date', $endDate]);
        }

        if ($categoryId !== null) {
            $query->andWhere(['income_category_id' => $categoryId]);
        }

        return (float) ($query->sum('amount') ?? 0);
    }

    /**
     * Get income count for a user within date range
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param string|null $startDate Start date (Y-m-d)
     * @param string|null $endDate End date (Y-m-d)
     * @return int
     */
    public static function getIncomeCount(
        ?int $userId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): int {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->where(['user_id' => $userId]);

        if ($startDate !== null) {
            $query->andWhere(['>=', 'entry_date', $startDate]);
        }

        if ($endDate !== null) {
            $query->andWhere(['<=', 'entry_date', $endDate]);
        }

        return (int) $query->count();
    }

    /**
     * Get income grouped by category
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param string|null $startDate Start date (Y-m-d)
     * @param string|null $endDate End date (Y-m-d)
     * @return array [category_name => total_amount]
     */
    public static function getIncomeByCategory(
        ?int $userId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->select(['c.name', 'SUM({{%incomes}}.amount) as total'])
            ->leftJoin('{{%income_categories}} c', 'c.id = {{%incomes}}.income_category_id')
            ->where(['{{%incomes}}.user_id' => $userId])
            ->groupBy(['{{%incomes}}.income_category_id', 'c.name'])
            ->orderBy(['total' => SORT_DESC]);

        if ($startDate !== null) {
            $query->andWhere(['>=', '{{%incomes}}.entry_date', $startDate]);
        }

        if ($endDate !== null) {
            $query->andWhere(['<=', '{{%incomes}}.entry_date', $endDate]);
        }

        return ArrayHelper::map($query->asArray()->all(), 'name', 'total');
    }

    /**
     * Get recent incomes
     *
     * @param int $limit Number of records to return
     * @param int|null $userId User ID (defaults to current user)
     * @return array
     */
    public static function getRecentIncomes(int $limit = 5, ?int $userId = null): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        return self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['entry_date' => SORT_DESC, 'created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Delete attachment file when deleting record
        $this->deleteAttachment();

        return true;
    }
}
