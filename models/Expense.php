<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;

/**
 * Expense Model - Represents expense transactions in the system.
 *
 * This model handles all expense-related operations including file attachments,
 * validation, and calculations. It supports multiple payment methods and
 * hierarchical expense categories.
 *
 * @property int $id
 * @property int $user_id
 * @property int $expense_category_id
 * @property string $expense_date
 * @property string|null $description
 * @property string $amount
 * @property string|null $filename
 * @property string|null $filepath
 * @property string $payment_method
 * @property string|null $reference
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property User $createdBy
 * @property ExpenseCategory $expenseCategory
 * @property User $updatedBy
 * @property User $user
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class Expense extends ActiveRecord
{
    /**
     * @var UploadedFile|null File upload instance
     */
    public $myFile = null;

    /**
     * Available payment methods
     */
    public const PAYMENT_CASH = 'Cash';
    public const PAYMENT_CARD = 'Card';
    public const PAYMENT_BANK = 'Bank';

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%expenses}}';
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
                ]
            ],
            \app\components\WorkspaceBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate(): bool
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
            [['user_id', 'expense_category_id', 'expense_date', 'amount'], 'required'],

            [['fbr_category'], 'string', 'max' => 100],

            // Integer fields
            [['user_id', 'workspace_id', 'expense_category_id', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],

            // Safe attributes
            [['fbr_category', 'expense_date'], 'safe'],

            // String validations
            [['description'], 'string'],
            [['filename'], 'string', 'max' => 96],
            [['filepath', 'reference'], 'string', 'max' => 191],

            // File upload validation
            [['myFile'], 'file', 'extensions' => 'png, jpg, jpeg, pdf', 'maxSize' => 12 * 1024 * 1024],

            // Payment method validation
            [['payment_method'], 'in', 'range' => array_keys(self::getPaymentMethods())],

            // Foreign key validations
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['expense_category_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExpenseCategory::class, 'targetAttribute' => ['expense_category_id' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['updated_by' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],

            // Trim whitespace
            [['reference', 'description', 'payment_method'], 'filter', 'filter' => fn ($value) => $value === null ? null : trim($value)],
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
            'expense_category_id' => Yii::t('app', 'Category'),
            'fbr_category' => 'FBR Tax Category',
            'expense_date' => Yii::t('app', 'Date'),
            'description' => Yii::t('app', 'Description'),
            'amount' => Yii::t('app', 'Amount'),
            'myFile' => Yii::t('app', 'Attachment'),
            'filename' => Yii::t('app', 'Attachment'),
            'filepath' => Yii::t('app', 'File Path'),
            'payment_method' => Yii::t('app', 'Payment Method'),
            'reference' => Yii::t('app', 'Reference'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    /**
     * Gets available payment methods
     *
     * @return array
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::PAYMENT_CASH => Yii::t('app', 'Cash'),
            self::PAYMENT_CARD => Yii::t('app', 'Card'),
            self::PAYMENT_BANK => Yii::t('app', 'Bank Transfer'),
        ];
    }

    /**
     * Gets the payment method badge CSS class
     *
     * @return string
     */
    public function getPaymentMethodBadgeClass(): string
    {
        $classes = [
            self::PAYMENT_CASH => 'badge-cash',
            self::PAYMENT_CARD => 'badge-card',
            self::PAYMENT_BANK => 'badge-bank',
        ];

        return $classes[$this->payment_method] ?? 'badge-secondary';
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[ExpenseCategory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpenseCategory(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ExpenseCategory::class, ['id' => 'expense_category_id']);
    }

    /**
     * Gets query for [[UpdatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Get the full file path for the uploaded attachment
     *
     * @return string|null
     */
    public function getImageFile(): ?string
    {
        return isset($this->filename) ? $this->filepath : null;
    }

    /**
     * Get the file extension
     *
     * @return string|null
     */
    public function getFileExtension(): ?string
    {
        if (empty($this->filepath)) {
            return null;
        }
        return strtolower(pathinfo($this->filepath, PATHINFO_EXTENSION));
    }

    /**
     * Check if the attachment is an image
     *
     * @return bool
     */
    public function isImageFile(): bool
    {
        $ext = $this->getFileExtension();
        return in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp']);
    }

    /**
     * Check if the attachment is a PDF
     *
     * @return bool
     */
    public function isPdfFile(): bool
    {
        return $this->getFileExtension() === 'pdf';
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

        return number_format($this->amount, 2);
    }

    /**
     * Check if expense has an attachment
     *
     * @return bool
     */
    public function hasAttachment(): bool
    {
        return !empty($this->filename) && !empty($this->filepath);
    }

    /**
     * Get the file icon class based on file type
     *
     * @return string
     */
    public function getFileIcon(): string
    {
        $ext = $this->getFileExtension();

        $icons = [
            'pdf' => 'bi-file-pdf text-danger',
            'png' => 'bi-file-image text-primary',
            'jpg' => 'bi-file-image text-primary',
            'jpeg' => 'bi-file-image text-primary',
            'gif' => 'bi-file-image text-primary',
            'webp' => 'bi-file-image text-primary',
        ];

        return $icons[$ext] ?? 'bi-file-earmark text-secondary';
    }

    /**
     * Get formatted file size
     *
     * @return string
     */
    public function getFileSizeFormatted(): string
    {
        if (empty($this->filepath)) {
            return 'N/A';
        }

        $filePath = Yii::getAlias('@webroot/' . $this->filepath);

        if (!file_exists($filePath)) {
            return 'N/A';
        }

        $bytes = filesize($filePath);

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        } else {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Get the public URL for the file
     *
     * @return string|null
     */
    public function getFileUrl(): ?string
    {
        if (empty($this->filepath)) {
            return null;
        }

        return Yii::getAlias('@web/' . $this->filepath);
    }

    /**
     * Calculate the total value of a specified attribute from a data provider.
     *
     * @param \yii\data\DataProviderInterface $provider The data provider containing the items
     * @param string $value The attribute whose values should be summed
     * @return string The total value formatted as currency
     */
    public static function pageTotal(\yii\data\DataProviderInterface $provider, string $value): string
    {
        $total = 0;

        foreach ($provider as $item) {
            $total += $item[$value];
        }

        return Yii::$app->currency->format($total);
    }

    /**
     * Get expenses summary for the current user within a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $query = self::find()
            ->where(['workspace_id' => Yii::$app->workspace->getId()]);

        if ($startDate && $endDate) {
            $query->andWhere(['between', 'expense_date', $startDate, $endDate]);
        }

        $total = $query->sum('amount') ?? 0;
        $count = $query->count();
        $average = $count > 0 ? $total / $count : 0;

        return [
            'total' => $total,
            'count' => $count,
            'average' => $average,
        ];
    }
}
