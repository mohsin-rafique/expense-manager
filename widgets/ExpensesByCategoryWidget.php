<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\db\Query;
use yii\helpers\Json;
use yii\web\View;

/**
 * ExpensesByCategoryWidget displays a horizontal bar chart of expenses by category
 *
 * This widget renders an ApexCharts horizontal bar chart showing the breakdown
 * of expenses by category for a specified period (default: current month).
 *
 * ## Features
 *
 * - Horizontal bar chart with category breakdown
 * - Configurable time period (current month, custom range)
 * - Customizable number of categories to display
 * - Responsive design
 * - Empty state handling
 *
 * ## Usage
 *
 * ```php
 * // Current month (default)
 * <?= ExpensesByCategoryWidget::widget() ?>
 *
 * // Custom date range
 * <?= ExpensesByCategoryWidget::widget([
 *     'startDate' => '2024-01-01',
 *     'endDate' => '2024-12-31',
 *     'maxCategories' => 10,
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ExpensesByCategoryWidget extends Widget
{
    /** @var int|null User ID for filtering data */
    public ?int $userId = null;

    /** @var string Widget title */
    public string $title = 'Expenses by Category';

    /** @var string|null Start date for filtering (Y-m-d format, null = current month) */
    public ?string $startDate = null;

    /** @var string|null End date for filtering (Y-m-d format, null = current month) */
    public ?string $endDate = null;

    /** @var int Maximum number of categories to display */
    public int $maxCategories = 10;

    /** @var string|null Custom CSS class for the widget container */
    public ?string $containerClass = null;

    /** @var int Chart height in pixels */
    public int $chartHeight = 350;

    /** @var array Chart colors */
    public array $chartColors = [
        '--em-primary',
        '--em-success',
        '--em-warning',
        '--em-danger',
        '--em-info',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
        '#6366F1',
    ];

    /** @var string|null Unique widget ID */
    private ?string $_widgetId = null;

    /** @var array Category data [name => amount] */
    private array $_categories = [];

    /** @var float Total expense amount */
    private float $_totalExpense = 0;

    /** @var string|null Period label for display */
    private ?string $_periodLabel = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this->_widgetId = $this->getId();

        if ($this->userId === null) {
            $this->userId = Yii::$app->user->id;
        }

        // Set default date range to current month if not specified
        if ($this->startDate === null || $this->endDate === null) {
            $this->startDate = date('Y-m-01');
            $this->endDate = date('Y-m-t');
            $this->_periodLabel = Yii::$app->formatter->asDate(time(), 'MMMM yyyy');
        } else {
            $this->_periodLabel = Yii::$app->formatter->asDate($this->startDate, 'MMM d')
                . ' - ' . Yii::$app->formatter->asDate($this->endDate, 'MMM d, yyyy');
        }

        $this->loadData();
    }

    /**
     * Load expense category data (parent-level only)
     *
     * Aggregates all child category expenses into their parent category.
     * Categories without a parent are treated as top-level.
     */
    protected function loadData(): void
    {
        // Get expenses grouped by top-level (parent) category
        // If a category has a parent_id, roll its expenses up to the parent
        $rows = (new Query())
            ->select([
                'COALESCE(parent.name, c.name) as name',
                'COALESCE(SUM(e.amount), 0) as total',
            ])
            ->from('{{%expense_categories}} c')
            ->leftJoin('{{%expense_categories}} parent', 'parent.id = c.parent_id')
            ->leftJoin('{{%expenses}} e', [
                'and',
                'e.expense_category_id = c.id',
                ['e.user_id' => $this->userId],
                ['BETWEEN', 'e.expense_date', $this->startDate, $this->endDate],
            ])
            ->where(['c.user_id' => $this->userId])
            ->groupBy(['COALESCE(parent.id, c.id)', 'COALESCE(parent.name, c.name)'])
            ->having(['>', 'total', 0])
            ->orderBy(['total' => SORT_DESC])
            ->limit($this->maxCategories)
            ->all();

        foreach ($rows as $row) {
            $this->_categories[$row['name']] = (float) $row['total'];
            $this->_totalExpense += (float) $row['total'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        $this->registerAssets();

        return $this->render('expenses-by-category', [
            'widgetId' => $this->_widgetId,
            'title' => Yii::t('app', $this->title),
            'containerClass' => $this->containerClass,
            'periodLabel' => $this->_periodLabel,
            'categories' => $this->_categories,
            'totalExpense' => $this->_totalExpense,
            'hasData' => !empty($this->_categories),
            'chartHeight' => $this->chartHeight,
        ]);
    }

    /**
     * Register JavaScript assets for the chart
     */
    protected function registerAssets(): void
    {
        if (empty($this->_categories)) {
            return;
        }

        $view = $this->getView();
        $chartId = 'categoryBarChart_' . $this->_widgetId;

        $categoryNames = array_keys($this->_categories);
        $categoryValues = array_values($this->_categories);

        $namesJson = Json::encode($categoryNames);
        $valuesJson = Json::encode($categoryValues);
        $colorsJson = Json::encode(array_slice($this->chartColors, 0, count($categoryNames)));
        $seriesLabel = Yii::t('app', 'Expense');
        $height = $this->chartHeight;

        $js = <<<JS
        (function() {
            'use strict';

            var chartElement = document.getElementById('{$chartId}');
            if (!chartElement || typeof ApexCharts === 'undefined') return;

            // Parse CSS variable colors
            var colors = {$colorsJson}.map(function(c) {
                if (c.startsWith('--')) {
                    return getComputedStyle(document.documentElement).getPropertyValue(c).trim() || '#6c757d';
                }
                return c;
            });

            var options = {
                series: [{
                    name: '{$seriesLabel}',
                    data: {$valuesJson}
                }],
                chart: {
                    type: 'bar',
                    height: {$height},
                    fontFamily: 'Inter, -apple-system, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        distributed: true,
                        borderRadius: 4,
                        barHeight: '70%',
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                colors: colors,
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    style: {
                        colors: ['#333'],
                        fontSize: '12px',
                        fontWeight: 500
                    },
                    formatter: function(val) {
                        return val.toLocaleString();
                    },
                    offsetX: 5
                },
                stroke: {
                    width: 0
                },
                xaxis: {
                    categories: {$namesJson},
                    labels: {
                        formatter: function(val) {
                            return val.toLocaleString();
                        }
                    }
                },
                yaxis: {
                    labels: {
                        maxWidth: 150,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } }
                },
                legend: {
                    show: false
                },
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
                        yaxis: {
                            labels: { maxWidth: 100 }
                        }
                    }
                }]
            };

            new ApexCharts(chartElement, options).render();
        })();
        JS;

        $view->registerJs($js, View::POS_END);
    }

    /**
     * Get categories data
     *
     * @return array
     */
    public function getCategories(): array
    {
        return $this->_categories;
    }

    /**
     * Get total expense
     *
     * @return float
     */
    public function getTotalExpense(): float
    {
        return $this->_totalExpense;
    }
}
