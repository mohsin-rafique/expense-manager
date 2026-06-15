<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use app\services\FiscalYearService;

/**
 * Budget model for the "{{%budgets}}" table.
 *
 * A budget defines a spending cap (for an expense category) or a target
 * (for an income category) over a recurring period - monthly, yearly, or the
 * application's fiscal year. Spending/earning for the *current* period is
 * computed on the fly from the related transactions, including any descendant
 * categories for hierarchical expense categories.
 *
 * @property int $id
 * @property int $user_id
 * @property string $category_type   'expense' | 'income'
 * @property int $category_id
 * @property string $period_type     'monthly' | 'yearly' | 'fiscal'
 * @property string $amount
 * @property int $alert_threshold    Percentage (1-100) at which a warning is raised
 * @property bool $email_alerts
 * @property string|null $note
 * @property bool $status
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property User $user
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class Budget extends ActiveRecord
{
    /** Category types */
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_INCOME = 'income';

    /** Period types */
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_YEARLY = 'yearly';
    public const PERIOD_FISCAL = 'fiscal';

    /** Status */
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    /** Alert levels returned by getStatusLevel() */
    public const LEVEL_SAFE = 'safe';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_OVER = 'over';

    /** @var float|null Cached spent amount for the current period */
    private ?float $_spent = null;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%budgets}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
            \app\components\WorkspaceBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate(): bool
    {
        // Clean amount format (strip thousand separators) before validation
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
            [['user_id', 'category_type', 'category_id', 'period_type', 'amount'], 'required'],

            [['user_id', 'workspace_id', 'category_id', 'alert_threshold'], 'integer'],
            [['email_alerts', 'status'], 'boolean'],

            [['amount'], 'number', 'min' => 0.01],

            [['note'], 'string', 'max' => 191],
            [['note'], 'trim'],

            [['category_type'], 'in', 'range' => array_keys(self::getCategoryTypeOptions())],
            [['period_type'], 'in', 'range' => array_keys(self::getPeriodTypeOptions())],

            [['alert_threshold'], 'default', 'value' => 80],
            [['alert_threshold'], 'integer', 'min' => 1, 'max' => 100],

            [['email_alerts'], 'default', 'value' => false],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],

            // The referenced category must exist and belong to the user
            [['category_id'], 'validateCategory'],

            // One budget per category + period per user
            [
                ['category_id'],
                'unique',
                'targetAttribute' => ['workspace_id', 'category_type', 'category_id', 'period_type'],
                'message' => Yii::t('app', 'A budget for this category and period already exists.'),
            ],

            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * Validates that the chosen category exists and belongs to the user.
     *
     * @param string $attribute
     */
    public function validateCategory(string $attribute): void
    {
        $model = $this->getCategoryModel();

        if ($model === null || (int) $model->workspace_id !== (int) $this->workspace_id) {
            $this->addError($attribute, Yii::t('app', 'The selected category is invalid.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User'),
            'category_type' => Yii::t('app', 'Category Type'),
            'category_id' => Yii::t('app', 'Category'),
            'period_type' => Yii::t('app', 'Period'),
            'amount' => Yii::t('app', 'Budget Amount'),
            'alert_threshold' => Yii::t('app', 'Alert Threshold'),
            'email_alerts' => Yii::t('app', 'Email Alerts'),
            'note' => Yii::t('app', 'Note'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    // ─── Option lists ────────────────────────────────────────────────

    /**
     * Category type options [value => label].
     *
     * @return array
     */
    public static function getCategoryTypeOptions(): array
    {
        return [
            self::TYPE_EXPENSE => Yii::t('app', 'Expense'),
            self::TYPE_INCOME => Yii::t('app', 'Income'),
        ];
    }

    /**
     * Period type options [value => label].
     *
     * @return array
     */
    public static function getPeriodTypeOptions(): array
    {
        return [
            self::PERIOD_MONTHLY => Yii::t('app', 'Monthly'),
            self::PERIOD_YEARLY => Yii::t('app', 'Yearly'),
            self::PERIOD_FISCAL => Yii::t('app', 'Fiscal Year'),
        ];
    }

    /**
     * Status options [value => label].
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
        ];
    }

    // ─── Relations & category helpers ────────────────────────────────

    /**
     * @return ActiveQuery
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Returns whether this budget targets an expense category.
     *
     * @return bool
     */
    public function isExpense(): bool
    {
        return $this->category_type === self::TYPE_EXPENSE;
    }

    /**
     * Returns the category model (ExpenseCategory or IncomeCategory) this
     * budget targets, or null if it no longer exists.
     *
     * @return ExpenseCategory|IncomeCategory|null
     */
    public function getCategoryModel()
    {
        if ($this->category_id === null) {
            return null;
        }

        return $this->isExpense()
            ? ExpenseCategory::findOne($this->category_id)
            : IncomeCategory::findOne($this->category_id);
    }

    /**
     * Returns the display name of the target category.
     *
     * @return string
     */
    public function getCategoryName(): string
    {
        $model = $this->getCategoryModel();

        if ($model === null) {
            return Yii::t('app', '(deleted category)');
        }

        // Expense categories support a hierarchical path
        if ($this->isExpense() && method_exists($model, 'getFullPath')) {
            return $model->getFullPath();
        }

        return $model->name;
    }

    /**
     * Returns the category ids whose transactions count toward this budget:
     * the category itself plus, for hierarchical expense categories, all of
     * its descendants.
     *
     * @return int[]
     */
    public function getCategoryIds(): array
    {
        $ids = [$this->category_id];

        $model = $this->getCategoryModel();
        if ($model !== null && method_exists($model, 'getDescendantIds')) {
            $ids = array_merge($ids, $model->getDescendantIds());
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    // ─── Period resolution ───────────────────────────────────────────

    /**
     * Resolves the current period window for this budget.
     *
     * @return array{startDate: string, endDate: string, label: string}
     */
    public function getCurrentPeriod(): array
    {
        switch ($this->period_type) {
            case self::PERIOD_YEARLY:
                $year = date('Y');
                return [
                    'startDate' => "{$year}-01-01",
                    'endDate' => "{$year}-12-31",
                    'label' => $year,
                ];

            case self::PERIOD_FISCAL:
                $fy = (new FiscalYearService())->getCurrentFiscalYear();
                return [
                    'startDate' => $fy['startDate'],
                    'endDate' => $fy['endDate'],
                    'label' => $fy['label'],
                ];

            case self::PERIOD_MONTHLY:
            default:
                return [
                    'startDate' => date('Y-m-01'),
                    'endDate' => date('Y-m-t'),
                    'label' => Yii::$app->formatter->asDate(time(), 'MMMM yyyy'),
                ];
        }
    }

    /**
     * Returns the human-readable period label (e.g. "June 2026", "FY 2025-26").
     *
     * @return string
     */
    public function getPeriodLabel(): string
    {
        return $this->getCurrentPeriod()['label'];
    }

    /**
     * Returns the period type display label.
     *
     * @return string
     */
    public function getPeriodTypeLabel(): string
    {
        return self::getPeriodTypeOptions()[$this->period_type] ?? $this->period_type;
    }

    /**
     * Returns true if the given date (Y-m-d) falls within this budget's
     * current period.
     *
     * @param string $date
     * @return bool
     */
    public function coversDate(string $date): bool
    {
        $period = $this->getCurrentPeriod();
        return $date >= $period['startDate'] && $date <= $period['endDate'];
    }

    // ─── Spending / progress ─────────────────────────────────────────

    /**
     * Returns the amount spent (expense) or earned (income) toward this budget
     * within the current period. Result is cached per request.
     *
     * @param bool $refresh Force a recomputation
     * @return float
     */
    public function getSpentAmount(bool $refresh = false): float
    {
        if ($this->_spent !== null && !$refresh) {
            return $this->_spent;
        }

        $period = $this->getCurrentPeriod();
        $categoryIds = $this->getCategoryIds();

        if ($this->isExpense()) {
            $query = Expense::find()
                ->where(['workspace_id' => $this->workspace_id])
                ->andWhere(['expense_category_id' => $categoryIds])
                ->andWhere(['between', 'expense_date', $period['startDate'], $period['endDate']]);
        } else {
            $query = Income::find()
                ->where(['workspace_id' => $this->workspace_id])
                ->andWhere(['income_category_id' => $categoryIds])
                ->andWhere(['between', 'entry_date', $period['startDate'], $period['endDate']]);
        }

        $this->_spent = (float) ($query->sum('amount') ?? 0);

        return $this->_spent;
    }

    /**
     * Returns the remaining budget amount (may be negative if over budget).
     *
     * @return float
     */
    public function getRemaining(): float
    {
        return (float) $this->amount - $this->getSpentAmount();
    }

    /**
     * Returns the percentage of the budget consumed (0..n, not capped).
     *
     * @return float
     */
    public function getPercentage(): float
    {
        $amount = (float) $this->amount;
        if ($amount <= 0) {
            return 0.0;
        }

        return round(($this->getSpentAmount() / $amount) * 100, 1);
    }

    /**
     * Returns the alert level for the current spending.
     *
     * For expense budgets: over (>=100%), warning (>= threshold), safe otherwise.
     * For income budgets: "over" means the target was met or exceeded (good).
     *
     * @return string One of LEVEL_SAFE, LEVEL_WARNING, LEVEL_OVER
     */
    public function getStatusLevel(): string
    {
        $pct = $this->getPercentage();

        if ($pct >= 100) {
            return self::LEVEL_OVER;
        }

        if ($pct >= $this->alert_threshold) {
            return self::LEVEL_WARNING;
        }

        return self::LEVEL_SAFE;
    }

    /**
     * Returns whether the alert threshold has been reached or exceeded.
     *
     * @return bool
     */
    public function isAlerting(): bool
    {
        return $this->getStatusLevel() !== self::LEVEL_SAFE;
    }

    /**
     * Returns a Bootstrap contextual color key for the current level.
     * Income targets invert the meaning (reaching the target is positive).
     *
     * @return string e.g. 'success', 'warning', 'danger'
     */
    public function getColor(): string
    {
        $level = $this->getStatusLevel();

        if ($this->isExpense()) {
            return [
                self::LEVEL_SAFE => 'success',
                self::LEVEL_WARNING => 'warning',
                self::LEVEL_OVER => 'danger',
            ][$level];
        }

        // Income target: more is better
        return [
            self::LEVEL_SAFE => 'info',
            self::LEVEL_WARNING => 'primary',
            self::LEVEL_OVER => 'success',
        ][$level];
    }

    /**
     * Returns the progress-bar width as a capped percentage string (0-100).
     *
     * @return float
     */
    public function getProgressWidth(): float
    {
        return min(100, max(0, $this->getPercentage()));
    }

    /**
     * Returns a short human-readable status label.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        switch ($this->getStatusLevel()) {
            case self::LEVEL_OVER:
                return $this->isExpense()
                    ? Yii::t('app', 'Over budget')
                    : Yii::t('app', 'Target reached');
            case self::LEVEL_WARNING:
                return $this->isExpense()
                    ? Yii::t('app', 'Approaching limit')
                    : Yii::t('app', 'Almost there');
            default:
                return Yii::t('app', 'On track');
        }
    }

    /**
     * Returns the formatted budget amount with currency.
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        return Yii::$app->currency->format((float) $this->amount);
    }
}
