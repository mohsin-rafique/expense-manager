<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\View;

/**
 * ComparativeAnalysisPanel Widget
 *
 * Self-contained widget that fetches and displays fiscal year financial metrics
 * with expense category breakdown chart.
 *
 * ## Features
 *
 * - Displays fiscal year revenue, expenditure, and net position
 * - Shows expense distribution by parent category (horizontal bar chart)
 * - Compares with previous fiscal year (optional)
 * - Fully responsive design
 * - Uses Bootstrap Icons for consistency
 *
 * ## Usage
 *
 * ```php
 * <?= ComparativeAnalysisPanel::widget([
 *     'fiscalStartDate' => '2024-07-01',
 *     'fiscalEndDate' => '2025-06-30',
 *     'fiscalYearLabel' => 'FY 2024-25',
 *     'showTrendIndicators' => true,
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ComparativeAnalysisPanel extends Widget
{
    /** @var string Fiscal year start date (Y-m-d format) */
    public $fiscalStartDate;

    /** @var string Fiscal year end date (Y-m-d format) */
    public $fiscalEndDate;

    /** @var int|null User ID (defaults to current logged-in user) */
    public $userId;

    /** @var string Fiscal year display label */
    public $fiscalYearLabel = '';

    /** @var string Widget title */
    public $title = 'Comparative Analysis';

    /** @var string Widget subtitle */
    public $subtitle = 'Fiscal year financial performance';

    /** @var array Custom colors for chart bars */
    public $chartColors = [
        '--em-primary',
        '--em-success',
        '--em-warning',
        '--em-danger',
        '--em-info',
        '--em-secondary',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
    ];

    /** @var string Widget container CSS class */
    public $containerClass = '';

    /** @var bool Show percentage change indicators */
    public $showTrendIndicators = true;

    /** @var bool Enable comparison with previous fiscal year */
    public $enablePreviousPeriodComparison = true;

    /** @var int Maximum number of categories to display in chart */
    public $maxCategories = 10;

    /** @var string Income table name */
    public $incomeTable = '{{%incomes}}';

    /** @var string Expense table name */
    public $expenseTable = '{{%expenses}}';

    /** @var string Expense category table name */
    public $expenseCategoryTable = '{{%expense_categories}}';

    /** @var string Unique widget ID */
    private $_widgetId;

    /** @var float Calculated revenue */
    private $_revenue;

    /** @var float Calculated expenditure */
    private $_expenditure;

    /** @var array Expense categories data */
    private $_ExpenseCategory = [];

    /** @var array Previous period comparison data */
    private $_previousPeriodData = [];

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

        $this->validateConfiguration();

        if (empty($this->fiscalYearLabel)) {
            $this->fiscalYearLabel = $this->generateFiscalYearLabel();
        }

        $this->loadFinancialData();
    }

    /**
     * Validate widget configuration
     *
     * @throws InvalidConfigException
     */
    protected function validateConfiguration(): void
    {
        if (empty($this->fiscalStartDate)) {
            throw new InvalidConfigException('Property "fiscalStartDate" must be set.');
        }

        if (empty($this->fiscalEndDate)) {
            throw new InvalidConfigException('Property "fiscalEndDate" must be set.');
        }

        if ($this->userId === null) {
            throw new InvalidConfigException('User must be logged in or "userId" must be set.');
        }
    }

    /**
     * Generate fiscal year label from dates
     *
     * @return string
     */
    protected function generateFiscalYearLabel(): string
    {
        $startYear = date('Y', strtotime($this->fiscalStartDate));
        $endYear = date('Y', strtotime($this->fiscalEndDate));

        if ($startYear === $endYear) {
            return Yii::t('app', 'FY {year}', ['year' => $startYear]);
        }

        return Yii::t('app', 'FY {startYear}-{endYear}', [
            'startYear' => $startYear,
            'endYear' => substr($endYear, -2),
        ]);
    }

    /**
     * Load all financial data from database
     */
    protected function loadFinancialData(): void
    {
        $this->_revenue = $this->fetchRevenue($this->fiscalStartDate, $this->fiscalEndDate);
        $this->_expenditure = $this->fetchExpenditure($this->fiscalStartDate, $this->fiscalEndDate);
        $this->_ExpenseCategory = $this->fetchExpenseCategory($this->fiscalStartDate, $this->fiscalEndDate);

        if ($this->enablePreviousPeriodComparison) {
            $this->loadPreviousPeriodData();
        }
    }

    /**
     * Fetch total revenue for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    protected function fetchRevenue(string $startDate, string $endDate): float
    {
        $result = Yii::$app->db->createCommand(
            "SELECT COALESCE(SUM(amount), 0)
             FROM {$this->incomeTable}
             WHERE workspace_id = :user
             AND entry_date BETWEEN :start AND :end"
        )->bindValues([
            ':user' => $this->userId,
            ':start' => $startDate,
            ':end' => $endDate,
        ])->queryScalar();

        return (float) $result;
    }

    /**
     * Fetch total expenditure for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    protected function fetchExpenditure(string $startDate, string $endDate): float
    {
        $result = Yii::$app->db->createCommand(
            "SELECT COALESCE(SUM(amount), 0)
             FROM {$this->expenseTable}
             WHERE workspace_id = :user
             AND expense_date BETWEEN :start AND :end"
        )->bindValues([
            ':user' => $this->userId,
            ':start' => $startDate,
            ':end' => $endDate,
        ])->queryScalar();

        return (float) $result;
    }

    /**
     * Fetch expense categories with totals (parent-level only)
     *
     * Aggregates all child category expenses into their parent category.
     * Categories without a parent are treated as top-level.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function fetchExpenseCategory(string $startDate, string $endDate): array
    {
        $rows = Yii::$app->db->createCommand(
            "SELECT COALESCE(parent.name, c.name) as name,
                    COALESCE(SUM(e.amount), 0) as total
             FROM {$this->expenseCategoryTable} c
             LEFT JOIN {$this->expenseCategoryTable} parent
                ON parent.id = c.parent_id
             LEFT JOIN {$this->expenseTable} e
                ON e.expense_category_id = c.id
                AND e.workspace_id = :user
                AND e.expense_date BETWEEN :start AND :end
             WHERE c.workspace_id = :user
             GROUP BY COALESCE(parent.id, c.id), COALESCE(parent.name, c.name)
             HAVING total > 0
             ORDER BY total DESC
             LIMIT :limit"
        )->bindValues([
            ':user' => $this->userId,
            ':start' => $startDate,
            ':end' => $endDate,
            ':limit' => $this->maxCategories,
        ])->queryAll();

        $categories = [];
        foreach ($rows as $row) {
            $categories[$row['name']] = (float) $row['total'];
        }

        return $categories;
    }

    /**
     * Load previous fiscal year data for trend comparison
     */
    protected function loadPreviousPeriodData(): void
    {
        $prevDates = $this->calculatePreviousFiscalYear();

        $prevRevenue = $this->fetchRevenue($prevDates['start'], $prevDates['end']);
        $prevExpenditure = $this->fetchExpenditure($prevDates['start'], $prevDates['end']);

        $this->_previousPeriodData = [
            'revenue' => $prevRevenue,
            'expenditure' => $prevExpenditure,
            'net' => $prevRevenue - $prevExpenditure,
        ];
    }

    /**
     * Calculate previous fiscal year date range
     *
     * @return array
     */
    protected function calculatePreviousFiscalYear(): array
    {
        $startDate = new \DateTime($this->fiscalStartDate);
        $endDate = new \DateTime($this->fiscalEndDate);

        $startDate->modify('-1 year');
        $endDate->modify('-1 year');

        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        $this->registerAssets();

        $totalExpenditure = array_sum($this->_ExpenseCategory);

        return $this->render('comparative-analysis', [
            'widgetId' => $this->_widgetId,
            'title' => Yii::t('app', $this->title),
            'subtitle' => Yii::t('app', $this->subtitle),
            'containerClass' => $this->containerClass,
            'fiscalYearLabel' => $this->fiscalYearLabel,
            'revenue' => $this->_revenue,
            'expenditure' => $this->_expenditure,
            'netPosition' => $this->getNetPosition(),
            'isNetPositive' => $this->isNetPositive(),
            'ExpenseCategory' => $this->_ExpenseCategory,
            'totalCategoryExpenditure' => $totalExpenditure,
            'categoryCount' => count($this->_ExpenseCategory),
            'showTrendIndicators' => $this->showTrendIndicators,
            'revenueTrend' => $this->getTrendIndicator('revenue'),
            'expenditureTrend' => $this->getTrendIndicator('expenditure'),
            'netTrend' => $this->getTrendIndicator('net'),
            'chartId' => 'bar_chart_fiscal_year_' . $this->_widgetId,
            'chartColors' => $this->chartColors,
        ]);
    }

    /**
     * Calculate net position (profit/loss)
     *
     * @return float
     */
    protected function getNetPosition(): float
    {
        return $this->_revenue - $this->_expenditure;
    }

    /**
     * Determine if net position is positive
     *
     * @return bool
     */
    protected function isNetPositive(): bool
    {
        return $this->getNetPosition() >= 0;
    }

    /**
     * Get trend indicator configuration
     *
     * @param string $type 'revenue', 'expenditure', or 'net'
     * @return array
     */
    protected function getTrendIndicator(string $type): array
    {
        if (!$this->showTrendIndicators || empty($this->_previousPeriodData)) {
            return $this->getDefaultIndicator($type);
        }

        $current = match ($type) {
            'revenue' => $this->_revenue,
            'expenditure' => $this->_expenditure,
            'net' => $this->getNetPosition(),
        };

        $previous = $this->_previousPeriodData[$type] ?? 0;
        $isIncrease = $current >= $previous;

        // Calculate percentage change
        $percentChange = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : 0;

        return match ($type) {
            'revenue' => [
                'icon' => $isIncrease ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill',
                'class' => $isIncrease ? 'text-success' : 'text-warning',
                'label' => $isIncrease ? Yii::t('app', 'Increased') : Yii::t('app', 'Decreased'),
                'percent' => $percentChange,
            ],
            'expenditure' => [
                'icon' => $isIncrease ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill',
                'class' => $isIncrease ? 'text-danger' : 'text-success',
                'label' => $isIncrease ? Yii::t('app', 'Increased') : Yii::t('app', 'Decreased'),
                'percent' => $percentChange,
            ],
            'net' => [
                'icon' => $this->isNetPositive() ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill',
                'class' => $this->isNetPositive() ? 'text-success' : 'text-danger',
                'label' => $this->isNetPositive() ? Yii::t('app', 'Surplus') : Yii::t('app', 'Deficit'),
                'percent' => $percentChange,
            ],
        };
    }

    /**
     * Get default indicator when no comparison data
     *
     * @param string $type
     * @return array
     */
    protected function getDefaultIndicator(string $type): array
    {
        return match ($type) {
            'revenue' => [
                'icon' => 'bi-graph-up-arrow',
                'class' => 'text-success',
                'label' => Yii::t('app', 'Revenue'),
                'percent' => null,
            ],
            'expenditure' => [
                'icon' => 'bi-graph-down-arrow',
                'class' => 'text-danger',
                'label' => Yii::t('app', 'Expenditure'),
                'percent' => null,
            ],
            'net' => [
                'icon' => $this->isNetPositive() ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill',
                'class' => $this->isNetPositive() ? 'text-success' : 'text-danger',
                'label' => $this->isNetPositive() ? Yii::t('app', 'Surplus') : Yii::t('app', 'Deficit'),
                'percent' => null,
            ],
        };
    }

    /**
     * Register required JavaScript assets
     */
    protected function registerAssets(): void
    {
        if (empty($this->_ExpenseCategory)) {
            return;
        }

        $view = $this->getView();
        $chartId = 'bar_chart_fiscal_year_' . $this->_widgetId;

        $categories = array_keys($this->_ExpenseCategory);
        $values = array_values($this->_ExpenseCategory);

        $categoriesJson = Json::encode($categories);
        $valuesJson = Json::encode($values);
        $colorsJson = Json::encode(array_slice($this->chartColors, 0, count($categories)));
        $seriesLabel = Yii::t('app', 'Expenditure');

        $js = <<<JS
        (function() {
            'use strict';

            var chartElement = document.getElementById("{$chartId}");
            if (!chartElement || typeof ApexCharts === 'undefined') return;

            var colors = {$colorsJson}.map(function(c) {
                if (c.startsWith('--')) {
                    return getComputedStyle(document.documentElement).getPropertyValue(c).trim() || '#6c757d';
                }
                return c;
            });

            var options = {
                chart: {
                    type: "bar",
                    height: '100%',
                    fontFamily: 'Inter, -apple-system, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                colors: colors,
                dataLabels: {
                    enabled: true,
                    textAnchor: "start",
                    style: {
                        colors: ["#374151"],
                        fontSize: '12px',
                        fontWeight: 500
                    },
                    formatter: function(val) {
                        return val.toLocaleString();
                    },
                    offsetX: 5
                },
                grid: {
                    borderColor: "#E5E7EB",
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } }
                },
                plotOptions: {
                    bar: {
                        distributed: true,
                        horizontal: true,
                        barHeight: "70%",
                        borderRadius: 4
                    }
                },
                series: [{
                    name: "{$seriesLabel}",
                    data: {$valuesJson}
                }],
                xaxis: {
                    categories: {$categoriesJson},
                    labels: {
                        formatter: function(val) {
                            return val.toLocaleString();
                        },
                        style: { fontSize: '12px', colors: '#6B7280' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '12px', colors: '#6B7280' },
                        maxWidth: 150
                    }
                },
                legend: { show: false },
                stroke: { width: 0 },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toLocaleString();
                        }
                    }
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        chart: { height: 300 },
                        dataLabels: { enabled: false },
                        yaxis: { labels: { maxWidth: 100 } }
                    }
                }]
            };

            new ApexCharts(chartElement, options).render();
        })();
        JS;

        $view->registerJs($js, View::POS_END);
    }

    /**
     * Get revenue value
     *
     * @return float
     */
    public function getRevenue(): float
    {
        return $this->_revenue;
    }

    /**
     * Get expenditure value
     *
     * @return float
     */
    public function getExpenditure(): float
    {
        return $this->_expenditure;
    }

    /**
     * Get expense categories
     *
     * @return array
     */
    public function getExpenseCategory(): array
    {
        return $this->_ExpenseCategory;
    }
}
