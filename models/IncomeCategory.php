<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%income_categories}}".
 *
 * Income categories allow users to organize and classify their income sources
 * for better financial tracking and reporting.
 *
 * @property int $id Category ID
 * @property int $user_id Owner user ID
 * @property string $name Category name
 * @property string|null $description Optional description
 * @property string|null $icon Bootstrap icon class (e.g., 'bi-wallet2')
 * @property string|null $color Hex color code (e.g., '#16a34a')
 * @property int $status Active/Inactive status
 * @property string $created_at Creation timestamp
 * @property string $updated_at Last update timestamp
 *
 * @property User $user Owner user relation
 * @property Income[] $incomes Related income records
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class IncomeCategory extends ActiveRecord
{
    /**
     * Status constants
     */
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    /**
     * Default icon and color values
     */
    public const DEFAULT_ICON = 'bi-folder';
    public const DEFAULT_COLOR = '#16a34a';

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%income_categories}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['name'], 'required'],
            [['user_id'], 'required', 'on' => 'create'],

            // Type validation
            [['user_id', 'status'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],

            // String length limits
            [['name'], 'string', 'max' => 100],
            [['icon'], 'string', 'max' => 50],
            [['color'], 'string', 'max' => 20],

            // Default values
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['icon'], 'default', 'value' => self::DEFAULT_ICON],
            [['color'], 'default', 'value' => self::DEFAULT_COLOR],

            // Unique name per user
            [
                ['name'],
                'unique',
                'targetAttribute' => ['name', 'user_id'],
                'message' => Yii::t('app', 'You already have a category with this name.'),
            ],

            // Foreign key validation
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],

            // Status validation
            [['status'], 'in', 'range' => [self::STATUS_INACTIVE, self::STATUS_ACTIVE]],

            // Color format validation
            [
                ['color'],
                'match',
                'pattern' => '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
                'message' => Yii::t('app', 'Invalid color format. Use hex format (e.g., #16a34a).'),
            ],

            // Sanitize name
            [['name'], 'trim'],
            [['name'], 'filter', 'filter' => function ($value) {
                return strip_tags($value);
            }],
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
            'name' => Yii::t('app', 'Category Name'),
            'description' => Yii::t('app', 'Description'),
            'icon' => Yii::t('app', 'Icon'),
            'color' => Yii::t('app', 'Color'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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
     * Gets query for [[Incomes]].
     *
     * @return ActiveQuery
     */
    public function getIncomes(): ActiveQuery
    {
        return $this->hasMany(Income::class, ['income_category_id' => 'id']);
    }

    /**
     * Returns status label
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->status === self::STATUS_ACTIVE
            ? Yii::t('app', 'Active')
            : Yii::t('app', 'Inactive');
    }

    /**
     * Returns status badge HTML
     *
     * @return string HTML badge
     */
    public function getStatusBadge(): string
    {
        if ($this->status === self::STATUS_ACTIVE) {
            return '<span class="badge bg-success-subtle text-success">'
                . '<i class="bi bi-check-circle me-1"></i>'
                . Yii::t('app', 'Active')
                . '</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary">'
            . '<i class="bi bi-x-circle me-1"></i>'
            . Yii::t('app', 'Inactive')
            . '</span>';
    }

    /**
     * Returns array of status options for dropdowns
     *
     * @return array [value => label]
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
        ];
    }

    /**
     * Returns income categories for dropdown list
     *
     * This is the primary method for populating category dropdowns in forms.
     * Returns categories for the current authenticated user.
     *
     * @param bool $activeOnly Whether to return only active categories (default: true)
     * @param int|null $userId User ID (defaults to current user)
     * @return array [id => name] for dropdown list
     *
     * @example
     * ```php
     * // In a form view
     * <?= $form->field($model, 'income_category_id')->dropDownList(
     *     IncomeCategory::getIncomeCategory(),
     *     ['prompt' => 'Select Category']
     * ) ?>
     *
     * // With 'All' prompt for filters
     * <?= $form->field($model, 'income_category_id')->dropDownList(
     *     IncomeCategory::getIncomeCategory(),
     *     ['prompt' => 'All']
     * ) ?>
     *
     * // Include inactive categories
     * <?= $form->field($model, 'income_category_id')->dropDownList(
     *     IncomeCategory::getIncomeCategory(false),
     *     ['prompt' => 'All']
     * ) ?>
     * ```
     */
    public static function getIncomeCategory(bool $activeOnly = true, ?int $userId = null): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->select(['id', 'name'])
            ->where(['user_id' => $userId])
            ->orderBy(['name' => SORT_ASC]);

        if ($activeOnly) {
            $query->andWhere(['status' => self::STATUS_ACTIVE]);
        }

        return ArrayHelper::map($query->asArray()->all(), 'id', 'name');
    }

    /**
     * Returns income categories with additional details for enhanced dropdowns
     *
     * Useful for dropdowns that need to display icons or colors.
     *
     * @param bool $activeOnly Whether to return only active categories
     * @param int|null $userId User ID (defaults to current user)
     * @return array Array of category data with id, name, icon, color
     *
     * @example
     * ```php
     * // Get categories with details for custom rendering
     * $categories = IncomeCategory::getIncomeCategoryWithDetails();
     * foreach ($categories as $category) {
     *     echo $category['icon'] . ' ' . $category['name'];
     * }
     * ```
     */
    public static function getIncomeCategoryWithDetails(bool $activeOnly = true, ?int $userId = null): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->select(['id', 'name', 'icon', 'color', 'description'])
            ->where(['user_id' => $userId])
            ->orderBy(['name' => SORT_ASC]);

        if ($activeOnly) {
            $query->andWhere(['status' => self::STATUS_ACTIVE]);
        }

        return $query->asArray()->all();
    }

    /**
     * Returns active categories for the current user
     *
     * @param int|null $userId User ID (defaults to current user)
     * @return array Categories indexed by ID (model objects)
     */
    public static function getActiveCategories(?int $userId = null): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        return self::find()
            ->where([
                'user_id' => $userId,
                'status' => self::STATUS_ACTIVE,
            ])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->all();
    }

    /**
     * Returns dropdown list of active categories
     *
     * @param int|null $userId User ID (defaults to current user)
     * @return array [id => name]
     * @deprecated Use getIncomeCategory() instead
     */
    public static function getDropdownList(?int $userId = null): array
    {
        return self::getIncomeCategory(true, $userId);
    }

    /**
     * Checks if category can be deleted (no associated records)
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        return !$this->getIncomes()->exists();
    }

    /**
     * Returns total income for this category
     *
     * @param string|null $startDate Start date filter (Y-m-d)
     * @param string|null $endDate End date filter (Y-m-d)
     * @return float
     */
    public function getTotalIncome(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->getIncomes();

        if ($startDate !== null) {
            $query->andWhere(['>=', 'income_date', $startDate]);
        }

        if ($endDate !== null) {
            $query->andWhere(['<=', 'income_date', $endDate]);
        }

        return (float) ($query->sum('amount') ?? 0);
    }

    /**
     * Returns the category name by ID
     *
     * @param int $id Category ID
     * @return string|null Category name or null if not found
     */
    public static function getCategoryNameById(int $id): ?string
    {
        $category = self::findOne($id);
        return $category ? $category->name : null;
    }

    /**
     * Find category by name for the current user
     *
     * @param string $name Category name
     * @param int|null $userId User ID (defaults to current user)
     * @return self|null
     */
    public static function findByName(string $name, ?int $userId = null): ?self
    {
        $userId = $userId ?? Yii::$app->user->id;

        return self::find()
            ->where([
                'name' => $name,
                'user_id' => $userId,
            ])
            ->one();
    }

    /**
     * Get count of incomes in this category
     *
     * @return int
     */
    public function getIncomeCount(): int
    {
        return (int) $this->getIncomes()->count();
    }
}
