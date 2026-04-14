<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%expense_categories}}".
 *
 * Expense categories support hierarchical (n-level) structure allowing users
 * to organize expenses in nested categories (e.g., Food > Groceries > Vegetables).
 *
 * @property int $id Category ID
 * @property int|null $parent_id Parent category ID (null for root categories)
 * @property int $user_id Owner user ID
 * @property string $name Category name
 * @property string|null $description Optional description
 * @property string|null $icon Bootstrap icon class (e.g., 'bi-cart')
 * @property string|null $color Hex color code (e.g., '#dc2626')
 * @property int $status Active/Inactive status
 * @property int|null $created_at Creation timestamp
 * @property int|null $updated_at Last update timestamp
 * @property int|null $created_by Creator user ID
 * @property int|null $updated_by Last updater user ID
 *
 * @property ExpenseCategory|null $parent Parent category relation
 * @property ExpenseCategory[] $children Child categories relation
 * @property User $user Owner user relation
 * @property Expense[] $expenses Related expense records
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ExpenseCategory extends ActiveRecord
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
    public const DEFAULT_COLOR = '#dc2626';

    /**
     * Maximum nesting depth allowed
     */
    public const MAX_DEPTH = 10;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%expense_categories}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
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
            [['parent_id', 'user_id', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],
            [['description'], 'string'],

            // String length limits
            [['name'], 'string', 'max' => 191],
            [['icon'], 'string', 'max' => 50],
            [['color'], 'string', 'max' => 20],

            // Default values
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['icon'], 'default', 'value' => self::DEFAULT_ICON],
            [['color'], 'default', 'value' => self::DEFAULT_COLOR],
            [['parent_id'], 'default', 'value' => null],

            // Unique name per user within same parent
            [
                ['name'],
                'unique',
                'targetAttribute' => ['name', 'user_id', 'parent_id'],
                'message' => Yii::t('app', 'You already have a category with this name at this level.'),
            ],

            // Foreign key validation
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],

            // Parent validation
            [
                ['parent_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => self::class,
                'targetAttribute' => ['parent_id' => 'id'],
                'filter' => function ($query) {
                    $query->andWhere(['user_id' => $this->user_id]);
                },
                'when' => function ($model) {
                    return $model->parent_id !== null;
                },
            ],

            // Prevent circular reference
            [['parent_id'], 'validateParent'],

            // Status validation
            [['status'], 'in', 'range' => [self::STATUS_INACTIVE, self::STATUS_ACTIVE]],

            // Color format validation
            [
                ['color'],
                'match',
                'pattern' => '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
                'message' => Yii::t('app', 'Invalid color format. Use hex format (e.g., #dc2626).'),
            ],

            // Sanitize name
            [['name'], 'trim'],
            [['name'], 'filter', 'filter' => function ($value) {
                return strip_tags($value);
            }],
        ];
    }

    /**
     * Validates that parent_id doesn't create a circular reference
     *
     * @param string $attribute The attribute being validated
     */
    public function validateParent(string $attribute): void
    {
        if ($this->parent_id === null) {
            return;
        }

        // Cannot be own parent
        if ($this->parent_id == $this->id) {
            $this->addError($attribute, Yii::t('app', 'Category cannot be its own parent.'));
            return;
        }

        // Check for circular reference in descendants
        if (!$this->isNewRecord) {
            $descendantIds = $this->getDescendantIds();
            if (in_array($this->parent_id, $descendantIds)) {
                $this->addError($attribute, Yii::t('app', 'Cannot move category under its own descendant.'));
                return;
            }
        }

        // Check max depth
        $parentDepth = $this->getParentDepth($this->parent_id);
        $childDepth = $this->isNewRecord ? 0 : $this->getMaxChildDepth();

        if (($parentDepth + $childDepth + 1) > self::MAX_DEPTH) {
            $this->addError($attribute, Yii::t('app', 'Maximum category depth ({max}) exceeded.', ['max' => self::MAX_DEPTH]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'parent_id' => Yii::t('app', 'Parent Category'),
            'user_id' => Yii::t('app', 'User'),
            'name' => Yii::t('app', 'Category Name'),
            'description' => Yii::t('app', 'Description'),
            'icon' => Yii::t('app', 'Icon'),
            'color' => Yii::t('app', 'Color'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return ActiveQuery
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[Children]].
     *
     * @return ActiveQuery
     */
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
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
     * Gets query for [[Expenses]].
     *
     * @return ActiveQuery
     */
    public function getExpenses(): ActiveQuery
    {
        return $this->hasMany(Expense::class, ['expense_category_id' => 'id']);
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
     * Returns the depth level of this category (0 = root)
     *
     * @return int
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent !== null) {
            $depth++;
            $parent = $parent->parent;

            // Safety check to prevent infinite loops
            if ($depth > self::MAX_DEPTH) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Returns the depth of a parent category by ID
     *
     * @param int $parentId Parent category ID
     * @return int
     */
    protected function getParentDepth(int $parentId): int
    {
        $parent = self::findOne($parentId);
        return $parent ? $parent->getDepth() + 1 : 0;
    }

    /**
     * Returns the maximum depth among all descendants
     *
     * @return int
     */
    protected function getMaxChildDepth(): int
    {
        $maxDepth = 0;

        foreach ($this->children as $child) {
            $childDepth = 1 + $child->getMaxChildDepth();
            $maxDepth = max($maxDepth, $childDepth);
        }

        return $maxDepth;
    }

    /**
     * Returns all descendant IDs (children, grandchildren, etc.)
     *
     * @return array
     */
    public function getDescendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    /**
     * Returns all ancestor IDs (parent, grandparent, etc.)
     *
     * @return array
     */
    public function getAncestorIds(): array
    {
        $ids = [];
        $parent = $this->parent;

        while ($parent !== null) {
            $ids[] = $parent->id;
            $parent = $parent->parent;

            if (count($ids) > self::MAX_DEPTH) {
                break;
            }
        }

        return $ids;
    }

    /**
     * Returns breadcrumb path from root to this category
     *
     * @return array Array of category names
     */
    public function getBreadcrumbPath(): array
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent !== null) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;

            if (count($path) > self::MAX_DEPTH) {
                break;
            }
        }

        return $path;
    }

    /**
     * Returns full path as string
     *
     * @param string $separator Path separator
     * @return string
     */
    public function getFullPath(string $separator = ' > '): string
    {
        return implode($separator, $this->getBreadcrumbPath());
    }

    /**
     * Checks if this category has children
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->getChildren()->exists();
    }

    /**
     * Checks if this category is a root category
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Checks if this category is a leaf (no children)
     *
     * @return bool
     */
    public function isLeaf(): bool
    {
        return !$this->hasChildren();
    }

    /**
     * Checks if category can be deleted (no associated expenses)
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        // Check if has expenses
        if ($this->getExpenses()->exists()) {
            return false;
        }

        // Check if children have expenses
        foreach ($this->children as $child) {
            if (!$child->canDelete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns total expense for this category (including children)
     *
     * @param string|null $startDate Start date filter (Y-m-d)
     * @param string|null $endDate End date filter (Y-m-d)
     * @param bool $includeChildren Whether to include children expenses
     * @return float
     */
    public function getTotalExpense(?string $startDate = null, ?string $endDate = null, bool $includeChildren = true): float
    {
        $query = $this->getExpenses();

        if ($startDate !== null) {
            $query->andWhere(['>=', 'expense_date', $startDate]);
        }

        if ($endDate !== null) {
            $query->andWhere(['<=', 'expense_date', $endDate]);
        }

        $total = (float) ($query->sum('amount') ?? 0);

        if ($includeChildren) {
            foreach ($this->children as $child) {
                $total += $child->getTotalExpense($startDate, $endDate, true);
            }
        }

        return $total;
    }

    /**
     * Returns expense categories for dropdown list (flat list)
     *
     * This is the primary method for populating category dropdowns in forms.
     * Returns a flat list of categories for the current authenticated user.
     *
     * @param bool $activeOnly Whether to return only active categories (default: true)
     * @param int|null $userId User ID (defaults to current user)
     * @return array [id => name] for dropdown list
     *
     * @example
     * ```php
     * // In a form view
     * <?= $form->field($model, 'expense_category_id')->dropDownList(
     *     ExpenseCategory::getExpenseCategory(),
     *     ['prompt' => 'Select Category']
     * ) ?>
     *
     * // With 'All' prompt for filters
     * <?= $form->field($model, 'expense_category_id')->dropDownList(
     *     ExpenseCategory::getExpenseCategory(),
     *     ['prompt' => 'All']
     * ) ?>
     * ```
     */
    public static function getExpenseCategory(bool $activeOnly = true, ?int $userId = null): array
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
     * Returns expense categories as hierarchical dropdown list
     *
     * Returns categories with hierarchy indication using indentation.
     * Useful for dropdowns that need to show parent-child relationships.
     *
     * @param bool $activeOnly Whether to return only active categories (default: true)
     * @param int|null $userId User ID (defaults to current user)
     * @param int|null $excludeId Category ID to exclude (with descendants)
     * @return array [id => name with indentation] for dropdown list
     *
     * @example
     * ```php
     * // Hierarchical dropdown showing:
     * // Food
     * // — Groceries
     * // — — Vegetables
     * // — Restaurants
     * <?= $form->field($model, 'expense_category_id')->dropDownList(
     *     ExpenseCategory::getExpenseCategoryHierarchy(),
     *     ['prompt' => 'Select Category']
     * ) ?>
     * ```
     */
    public static function getExpenseCategoryHierarchy(bool $activeOnly = true, ?int $userId = null, ?int $excludeId = null): array
    {
        return self::getDropdownList($userId, $excludeId, $activeOnly);
    }

    /**
     * Returns expense categories with additional details for enhanced dropdowns
     *
     * Useful for dropdowns that need to display icons or colors.
     *
     * @param bool $activeOnly Whether to return only active categories
     * @param int|null $userId User ID (defaults to current user)
     * @return array Array of category data with id, name, icon, color, parent_id
     *
     * @example
     * ```php
     * // Get categories with details for custom rendering
     * $categories = ExpenseCategory::getExpenseCategoryWithDetails();
     * foreach ($categories as $category) {
     *     echo $category['icon'] . ' ' . $category['name'];
     * }
     * ```
     */
    public static function getExpenseCategoryWithDetails(bool $activeOnly = true, ?int $userId = null): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->select(['id', 'name', 'parent_id', 'icon', 'color', 'description'])
            ->where(['user_id' => $userId])
            ->orderBy(['parent_id' => SORT_ASC, 'name' => SORT_ASC]);

        if ($activeOnly) {
            $query->andWhere(['status' => self::STATUS_ACTIVE]);
        }

        return $query->asArray()->all();
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
     * @param int|null $parentId Parent category ID (null for root)
     * @param int|null $userId User ID (defaults to current user)
     * @return self|null
     */
    public static function findByName(string $name, ?int $parentId = null, ?int $userId = null): ?self
    {
        $userId = $userId ?? Yii::$app->user->id;

        return self::find()
            ->where([
                'name' => $name,
                'parent_id' => $parentId,
                'user_id' => $userId,
            ])
            ->one();
    }

    /**
     * Get count of expenses in this category (direct only, not children)
     *
     * @return int
     */
    public function getExpenseCount(): int
    {
        return (int) $this->getExpenses()->count();
    }

    /**
     * Get count of expenses in this category including all descendants
     *
     * @return int
     */
    public function getTotalExpenseCount(): int
    {
        $count = $this->getExpenseCount();

        foreach ($this->children as $child) {
            $count += $child->getTotalExpenseCount();
        }

        return $count;
    }

    /**
     * Returns root categories for user
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param bool $activeOnly Only return active categories
     * @return array
     */
    public static function getRootCategories(?int $userId = null, bool $activeOnly = true): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->where([
                'user_id' => $userId,
                'parent_id' => null,
            ])
            ->orderBy(['name' => SORT_ASC]);

        if ($activeOnly) {
            $query->andWhere(['status' => self::STATUS_ACTIVE]);
        }

        return $query->all();
    }

    /**
     * Returns hierarchical tree structure for user
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param bool $activeOnly Only return active categories
     * @return array Nested array structure
     */
    public static function getTree(?int $userId = null, bool $activeOnly = true): array
    {
        $userId = $userId ?? Yii::$app->user->id;

        $query = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['parent_id' => SORT_ASC, 'name' => SORT_ASC]);

        if ($activeOnly) {
            $query->andWhere(['status' => self::STATUS_ACTIVE]);
        }

        $categories = $query->all();

        return self::buildTree($categories);
    }

    /**
     * Builds hierarchical tree from flat array
     *
     * @param array $categories Flat array of categories
     * @param int|null $parentId Parent ID to start from
     * @return array
     */
    protected static function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $children = self::buildTree($categories, $category->id);
                $tree[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'status' => $category->status,
                    'description' => $category->description,
                    'children' => $children,
                    'model' => $category,
                ];
            }
        }

        return $tree;
    }

    /**
     * Returns dropdown list with hierarchy indication
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param int|null $excludeId Category ID to exclude (with descendants)
     * @param bool $activeOnly Only return active categories
     * @return array [id => name with indentation]
     */
    public static function getDropdownList(?int $userId = null, ?int $excludeId = null, bool $activeOnly = true): array
    {
        $userId = $userId ?? Yii::$app->user->id;
        $tree = self::getTree($userId, $activeOnly);

        $excludeIds = [];
        if ($excludeId !== null) {
            $model = self::findOne($excludeId);
            if ($model) {
                $excludeIds = array_merge([$excludeId], $model->getDescendantIds());
            }
        }

        return self::flattenTreeForDropdown($tree, 0, $excludeIds);
    }

    /**
     * Flattens tree for dropdown with indentation
     *
     * @param array $tree Tree structure
     * @param int $depth Current depth level
     * @param array $excludeIds IDs to exclude
     * @return array
     */
    protected static function flattenTreeForDropdown(array $tree, int $depth = 0, array $excludeIds = []): array
    {
        $result = [];
        $prefix = str_repeat('— ', $depth);

        foreach ($tree as $node) {
            if (in_array($node['id'], $excludeIds)) {
                continue;
            }

            $result[$node['id']] = $prefix . $node['name'];

            if (!empty($node['children'])) {
                $result += self::flattenTreeForDropdown($node['children'], $depth + 1, $excludeIds);
            }
        }

        return $result;
    }

    /**
     * Returns JSON structure for jsTree or similar tree widgets
     *
     * @param int|null $userId User ID (defaults to current user)
     * @param bool $activeOnly Only return active categories
     * @return array
     */
    public static function getJsTreeData(?int $userId = null, bool $activeOnly = false): array
    {
        $tree = self::getTree($userId, $activeOnly);
        return self::convertToJsTreeFormat($tree);
    }

    /**
     * Converts tree to jsTree format
     *
     * @param array $tree Tree structure
     * @return array
     */
    protected static function convertToJsTreeFormat(array $tree): array
    {
        $result = [];

        foreach ($tree as $node) {
            $item = [
                'id' => $node['id'],
                'text' => $node['name'],
                'icon' => $node['icon'] ?? self::DEFAULT_ICON,
                'state' => [
                    'opened' => true,
                ],
                'li_attr' => [
                    'data-color' => $node['color'] ?? self::DEFAULT_COLOR,
                    'data-status' => $node['status'],
                ],
                'a_attr' => [
                    'class' => $node['status'] ? '' : 'text-muted',
                ],
            ];

            if (!empty($node['children'])) {
                $item['children'] = self::convertToJsTreeFormat($node['children']);
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Move category to a new parent
     *
     * @param int|null $newParentId New parent ID (null for root)
     * @return bool
     */
    public function moveTo(?int $newParentId): bool
    {
        $this->parent_id = $newParentId;
        return $this->save(true, ['parent_id', 'updated_at', 'updated_by']);
    }

    /**
     * Delete category and optionally its children
     *
     * @param bool $deleteChildren Whether to delete children or move to parent
     * @return bool
     * @throws \Exception
     */
    public function deleteWithChildren(bool $deleteChildren = false): bool
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($deleteChildren) {
                // Delete all descendants first
                foreach ($this->children as $child) {
                    if (!$child->deleteWithChildren(true)) {
                        throw new \Exception('Failed to delete child category: ' . $child->name);
                    }
                }
            } else {
                // Move children to this category's parent
                foreach ($this->children as $child) {
                    $child->parent_id = $this->parent_id;
                    if (!$child->save(false)) {
                        throw new \Exception('Failed to move child category: ' . $child->name);
                    }
                }
            }

            if (!$this->delete()) {
                throw new \Exception('Failed to delete category');
            }

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Failed to delete category: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
