<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;

/**
 * CurrentMonthPanelWidget displays current month financial metrics
 *
 * This widget supports two display modes:
 * - **summary**: Shows income, expense, and profit/loss for current month
 * - **evolution**: Compares current month with previous month
 *
 * ## Features
 *
 * - Current month income and expense totals
 * - Profit/loss calculation with trend indicators
 * - Month-over-month comparison (evolution mode)
 * - Customizable icons and styling
 * - Currency formatting via application currency component
 *
 * ## Usage
 *
 * ```php
 * // Summary mode (default)
 * <?= CurrentMonthPanelWidget::widget([
 *     'mode' => 'summary',
 * ]) ?>
 *
 * // Evolution mode (comparison)
 * <?= CurrentMonthPanelWidget::widget([
 *     'mode' => 'evolution',
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class CurrentMonthPanelWidget extends Widget
{
    /** @var int|null User ID for filtering data */
    public $userId;

    /** @var string Widget mode: 'summary' or 'evolution' */
    public $mode = 'summary';

    /** @var string Widget title (for summary mode) */
    public $title = 'Current Month Overview';

    /** @var string Widget title (for evolution mode) */
    public $evolutionTitle = 'Financial Evolution';

    /** @var string|null Custom CSS class for the widget container */
    public $containerClass = null;

    /** @var array Icons for each metric (using Bootstrap Icons) */
    public $icons = [
        'income' => 'bi-graph-up-arrow',
        'expense' => 'bi-graph-down-arrow',
        'profit' => 'bi-wallet2',
    ];

    /** @var string Unique widget ID */
    private $_widgetId;

    /** @var float Current month income */
    private $_currentMonthIncome = 0;

    /** @var float Current month expense */
    private $_currentMonthExpense = 0;

    /** @var float Current month profit/loss */
    private $_currentMonthProfitLoss = 0;

    /** @var float Previous month income */
    private $_previousMonthIncome = 0;

    /** @var float Previous month expense */
    private $_previousMonthExpense = 0;

    /** @var float Previous month balance */
    private $_balancePreviousMonth = 0;

    /** @var float Current month balance (including carryover) */
    private $_balanceCurrentMonth = 0;

    /** @var int Current month number */
    private $_currentMonth;

    /** @var int Current year */
    private $_currentYear;

    /** @var int Previous month number */
    private $_previousMonth;

    /** @var int Previous month year */
    private $_previousYear;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this->_widgetId = $this->getId();

        if ($this->userId === null) {
            $this->userId = Yii::$app->workspace->getId();
        }

        // Set date values
        $this->_currentMonth = (int) date('m');
        $this->_currentYear = (int) date('Y');
        $this->_previousMonth = ($this->_currentMonth == 1) ? 12 : $this->_currentMonth - 1;
        $this->_previousYear = ($this->_currentMonth == 1) ? $this->_currentYear - 1 : $this->_currentYear;

        // Load data based on mode
        if ($this->mode === 'evolution') {
            $this->calculateEvolutionMetrics();
        } else {
            $this->calculateSummaryMetrics();
        }
    }

    /**
     * Calculate current month summary metrics
     */
    protected function calculateSummaryMetrics(): void
    {
        $userId = (int) $this->userId;

        // Get current month income
        $this->_currentMonthIncome = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}}
             WHERE workspace_id = :userId
             AND MONTH(entry_date) = :month
             AND YEAR(entry_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $this->_currentMonth,
                ':year' => $this->_currentYear,
            ]
        )->queryScalar();

        // Get current month expense
        $this->_currentMonthExpense = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}}
             WHERE workspace_id = :userId
             AND MONTH(expense_date) = :month
             AND YEAR(expense_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $this->_currentMonth,
                ':year' => $this->_currentYear,
            ]
        )->queryScalar();

        // Calculate profit/loss
        $this->_currentMonthProfitLoss = $this->_currentMonthIncome - $this->_currentMonthExpense;
    }

    /**
     * Calculate evolution metrics (current and previous month comparison)
     */
    protected function calculateEvolutionMetrics(): void
    {
        $userId = (int) $this->userId;

        // Get current month income
        $this->_currentMonthIncome = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}}
             WHERE workspace_id = :userId
             AND MONTH(entry_date) = :month
             AND YEAR(entry_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $this->_currentMonth,
                ':year' => $this->_currentYear,
            ]
        )->queryScalar();

        // Get current month expense
        $this->_currentMonthExpense = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}}
             WHERE workspace_id = :userId
             AND MONTH(expense_date) = :month
             AND YEAR(expense_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $this->_currentMonth,
                ':year' => $this->_currentYear,
            ]
        )->queryScalar();

        // Get previous month income
        $this->_previousMonthIncome = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}}
             WHERE workspace_id = :userId
             AND MONTH(entry_date) = :month
             AND YEAR(entry_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $this->_previousMonth,
                ':year' => $this->_previousYear,
            ]
        )->queryScalar();

        // Get previous month expense
        $this->_previousMonthExpense = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}}
             WHERE workspace_id = :userId
             AND MONTH(expense_date) = :month
             AND YEAR(expense_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $this->_previousMonth,
                ':year' => $this->_previousYear,
            ]
        )->queryScalar();

        // Calculate balances
        $this->_balancePreviousMonth = $this->_previousMonthIncome - $this->_previousMonthExpense;

        // Current month balance includes previous month's carryover
        $currentMonthIncomeWithCarryover = $this->_currentMonthIncome + $this->_balancePreviousMonth;
        $this->_balanceCurrentMonth = $currentMonthIncomeWithCarryover - $this->_currentMonthExpense;
    }

    /**
     * Get month name from number
     *
     * @param int $month Month number (1-12)
     * @param int $year Year
     * @return string Formatted month name
     */
    protected function getMonthName(int $month, int $year): string
    {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        return Yii::$app->formatter->asDate($timestamp, 'MMMM yyyy');
    }

    /**
     * Get trend indicator for a value comparison
     *
     * @param float $current Current value
     * @param float $previous Previous value
     * @param bool $inverseLogic If true, decrease is positive (for expenses)
     * @return array
     */
    protected function getTrendIndicator(float $current, float $previous, bool $inverseLogic = false): array
    {
        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        $isIncrease = $current >= $previous;

        if ($inverseLogic) {
            // For expenses: decrease is good
            $isPositive = !$isIncrease;
        } else {
            // For income/balance: increase is good
            $isPositive = $isIncrease;
        }

        return [
            'icon' => $isIncrease ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill',
            'class' => $isPositive ? 'text-success' : 'text-danger',
            'percent' => round($change, 1),
            'isPositive' => $isPositive,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        if ($this->mode === 'evolution') {
            return $this->render('evolution-panel', [
                'widgetId' => $this->_widgetId,
                'title' => Yii::t('app', $this->evolutionTitle),
                'containerClass' => $this->containerClass,
                'currentMonthIncome' => $this->_currentMonthIncome,
                'currentMonthExpense' => $this->_currentMonthExpense,
                'previousMonthIncome' => $this->_previousMonthIncome,
                'previousMonthExpense' => $this->_previousMonthExpense,
                'balancePreviousMonth' => $this->_balancePreviousMonth,
                'balanceCurrentMonth' => $this->_balanceCurrentMonth,
                'currentMonthName' => $this->getMonthName($this->_currentMonth, $this->_currentYear),
                'previousMonthName' => $this->getMonthName($this->_previousMonth, $this->_previousYear),
                'isPositiveBalance' => $this->_balanceCurrentMonth >= 0,
                'incomeTrend' => $this->getTrendIndicator($this->_currentMonthIncome, $this->_previousMonthIncome),
                'expenseTrend' => $this->getTrendIndicator($this->_currentMonthExpense, $this->_previousMonthExpense, true),
                'balanceTrend' => $this->getTrendIndicator($this->_balanceCurrentMonth, $this->_balancePreviousMonth),
            ]);
        }

        // Default: summary mode
        $sparklineData = $this->getMonthlySparklineData(6);

        return $this->render('current-month-panel', [
            'widgetId' => $this->_widgetId,
            'title' => Yii::t('app', $this->title),
            'containerClass' => $this->containerClass,
            'income' => $this->_currentMonthIncome,
            'expense' => $this->_currentMonthExpense,
            'profitLoss' => $this->_currentMonthProfitLoss,
            'icons' => $this->icons,
            'currentMonthName' => $this->getMonthName($this->_currentMonth, $this->_currentYear),
            'isProfit' => $this->_currentMonthProfitLoss >= 0,
            'sparklineData' => $sparklineData,
        ]);
    }

    /**
     * Get monthly totals for the last N months (for sparkline charts)
     *
     * @param int $months Number of months to fetch
     * @return array ['labels' => [...], 'income' => [...], 'expense' => [...]]
     */
    protected function getMonthlySparklineData(int $months = 6): array
    {
        $userId = (int) $this->userId;
        $labels = [];
        $income = [];
        $expense = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = strtotime("-{$i} months");
            $m = (int) date('m', $date);
            $y = (int) date('Y', $date);
            $labels[] = date('M', $date);

            $income[] = (float) Yii::$app->db->createCommand(
                'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}}
             WHERE workspace_id = :userId AND MONTH(entry_date) = :month AND YEAR(entry_date) = :year',
                [':userId' => $userId, ':month' => $m, ':year' => $y]
            )->queryScalar();

            $expense[] = (float) Yii::$app->db->createCommand(
                'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}}
             WHERE workspace_id = :userId AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year',
                [':userId' => $userId, ':month' => $m, ':year' => $y]
            )->queryScalar();
        }

        return compact('labels', 'income', 'expense');
    }
}
