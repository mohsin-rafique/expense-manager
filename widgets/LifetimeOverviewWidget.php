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
 * LifetimeOverviewWidget displays cumulative financial metrics.
 *
 * This widget provides a comprehensive dashboard panel showing lifetime
 * financial KPIs including Gross Revenue, Operating Expenditure, and Net Position.
 *
 * ## Features
 *
 * - Displays cumulative income, expenses, and net position
 * - Calculates profit margin automatically
 * - Supports trend indicators
 * - Fully translatable labels
 * - Customizable currency formatting
 *
 * ## Usage
 *
 * ```php
 * <?= LifetimeOverviewWidget::widget([
 *     'userId' => Yii::$app->workspace->getId(),
 *     'showTrendIndicators' => true,
 *     'currencyCode' => 'PKR',
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class LifetimeOverviewWidget extends Widget
{
    /**
     * @var string|null Start date for lifetime calculations (Y-m-d format)
     */
    public ?string $startDate = null;

    /**
     * @var int|null User ID for filtering financial data
     */
    public ?int $userId = null;

    /**
     * @var bool Whether to display trend indicators
     */
    public bool $showTrendIndicators = true;

    /**
     * @var string Currency code for formatting
     */
    public string $currencyCode = 'PKR';

    /**
     * @var string|null Custom CSS class for the widget container
     */
    public ?string $containerClass = null;

    /**
     * @var string Widget title
     */
    public string $title = 'Lifetime Financial Overview';

    /**
     * @var string Widget subtitle
     */
    public string $subtitle = 'Cumulative Performance Metrics';

    /**
     * @var array Cached financial metrics
     */
    private array $_metrics = [];

    /**
     * @var array Configuration for metric cards
     */
    private array $_metricConfig = [];

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        if ($this->userId === null) {
            $this->userId = Yii::$app->workspace->getId();
        }

        $this->initMetricConfig();
        $this->loadFinancialMetrics();
    }

    /**
     * Initialize metric configuration with translations
     */
    protected function initMetricConfig(): void
    {
        $this->_metricConfig = [
            'grossRevenue' => [
                'label' => Yii::t('app', 'Gross Revenue'),
                'icon' => 'bi-graph-up-arrow',
                'colorClass' => 'success',
                'description' => Yii::t('app', 'Total lifetime earnings before deductions'),
            ],
            'operatingExpenditure' => [
                'label' => Yii::t('app', 'Operating Expenditure'),
                'icon' => 'bi-graph-down-arrow',
                'colorClass' => 'danger',
                'description' => Yii::t('app', 'Total lifetime operational costs'),
            ],
            'netPosition' => [
                'label' => Yii::t('app', 'Net Financial Position'),
                'icon' => 'bi-wallet2',
                'colorClass' => 'primary',
                'description' => Yii::t('app', 'Cumulative profit or loss indicator'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        return $this->render('lifetime-overview', [
            'metrics' => $this->_metrics,
            'config' => $this->_metricConfig,
            'showTrendIndicators' => $this->showTrendIndicators,
            'containerClass' => $this->containerClass,
            'title' => Yii::t('app', $this->title),
            'subtitle' => Yii::t('app', $this->subtitle),
            'widgetId' => $this->getId(),
        ]);
    }

    /**
     * Loads and calculates financial metrics from database
     */
    protected function loadFinancialMetrics(): void
    {
        $db = Yii::$app->db;
        $userId = (int) $this->userId;

        // Build income query with optional start date
        $incomeQuery = 'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}} WHERE workspace_id = :userId';
        $incomeParams = [':userId' => $userId];

        if ($this->startDate !== null) {
            $incomeQuery .= ' AND entry_date >= :startDate';
            $incomeParams[':startDate'] = $this->startDate;
        }

        // Build expense query with optional start date
        $expenseQuery = 'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}} WHERE workspace_id = :userId';
        $expenseParams = [':userId' => $userId];

        if ($this->startDate !== null) {
            $expenseQuery .= ' AND expense_date >= :startDate';
            $expenseParams[':startDate'] = $this->startDate;
        }

        // Aggregate Gross Revenue (Lifetime Income)
        $grossRevenue = (float) $db->createCommand($incomeQuery, $incomeParams)->queryScalar();

        // Aggregate Operating Expenditure (Lifetime Expenses)
        $operatingExpenditure = (float) $db->createCommand($expenseQuery, $expenseParams)->queryScalar();

        // Calculate Net Financial Position (P&L)
        $netPosition = $grossRevenue - $operatingExpenditure;

        // Calculate performance indicators
        $profitMargin = $grossRevenue > 0
            ? round(($netPosition / $grossRevenue) * 100, 2)
            : 0;

        // Expense ratio (what % of revenue is spent)
        $expenseRatio = $grossRevenue > 0
            ? round(($operatingExpenditure / $grossRevenue) * 100, 1)
            : 0;

        $this->_metrics = [
            'grossRevenue' => [
                'value' => $grossRevenue,
                'formatted' => $this->formatCurrency($grossRevenue),
                'trend' => 'positive',
            ],
            'operatingExpenditure' => [
                'value' => $operatingExpenditure,
                'formatted' => $this->formatCurrency($operatingExpenditure),
                'trend' => 'neutral',
                'expenseRatio' => $expenseRatio,
            ],
            'netPosition' => [
                'value' => $netPosition,
                'formatted' => $this->formatCurrency(abs($netPosition)),
                'trend' => $netPosition >= 0 ? 'positive' : 'negative',
                'profitMargin' => $profitMargin,
                'isNegative' => $netPosition < 0,
            ],
        ];
    }

    /**
     * Formats amount as currency
     *
     * @param float $amount
     * @return string
     */
    protected function formatCurrency(float $amount): string
    {
        return Yii::$app->currency->format($amount);
    }

    /**
     * Returns the trend indicator configuration
     *
     * @param string $trend Trend type: positive, negative, neutral
     * @return array Icon and class configuration
     */
    public static function getTrendIndicator(string $trend): array
    {
        return match ($trend) {
            'positive' => [
                'icon' => 'bi-arrow-up-circle-fill',
                'class' => 'text-success',
                'label' => Yii::t('app', 'Positive trend'),
            ],
            'negative' => [
                'icon' => 'bi-arrow-down-circle-fill',
                'class' => 'text-danger',
                'label' => Yii::t('app', 'Negative trend'),
            ],
            default => [
                'icon' => 'bi-dash-circle',
                'class' => 'text-secondary',
                'label' => Yii::t('app', 'Neutral trend'),
            ],
        };
    }

    /**
     * Get metrics data (for external access if needed)
     *
     * @return array
     */
    public function getMetrics(): array
    {
        return $this->_metrics;
    }
}
