<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Json;
use yii\web\View;

/**
 * MonthlyPerformanceWidget displays a donut chart showing balance vs expense ratio
 *
 * This widget renders an ApexCharts donut chart comparing the current month's
 * balance (savings) against expenses, providing a visual representation of
 * spending efficiency.
 *
 * ## Features
 *
 * - Donut chart with balance vs expense breakdown
 * - Percentage display in center
 * - Responsive design
 * - Customizable colors and styling
 * - Empty state handling
 * - Uses user's currency settings from the application
 *
 * ## Usage
 *
 * ```php
 * <?= MonthlyPerformanceWidget::widget() ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class MonthlyPerformanceWidget extends Widget
{
    /** @var int|null User ID for filtering data */
    public $userId;

    /** @var string Widget title */
    public $title = 'Monthly Performance';

    /** @var string|null Custom CSS class for the widget container */
    public $containerClass = null;

    /** @var int Chart height in pixels */
    public $chartHeight = 320;

    /** @var array Chart colors [balance, expense] */
    public $chartColors = ['--em-success', '--em-danger'];

    /** @var string Unique widget ID */
    private $_widgetId;

    /** @var float Current month income */
    private $_income = 0;

    /** @var float Current month expense */
    private $_expense = 0;

    /** @var float Current month balance */
    private $_balance = 0;

    /** @var string Current month name */
    private $_currentMonthName;

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

        $this->_currentMonthName = Yii::$app->formatter->asDate(time(), 'MMMM yyyy');

        $this->loadData();
    }

    /**
     * Load current month financial data
     */
    protected function loadData(): void
    {
        $userId = (int) $this->userId;
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        // Get current month income
        $this->_income = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}}
             WHERE workspace_id = :userId
             AND MONTH(entry_date) = :month
             AND YEAR(entry_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $currentMonth,
                ':year' => $currentYear,
            ]
        )->queryScalar();

        // Get current month expense
        $this->_expense = (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}}
             WHERE workspace_id = :userId
             AND MONTH(expense_date) = :month
             AND YEAR(expense_date) = :year',
            [
                ':userId' => $userId,
                ':month' => $currentMonth,
                ':year' => $currentYear,
            ]
        )->queryScalar();

        // Calculate balance (savings)
        $this->_balance = max(0, $this->_income - $this->_expense);
    }

    /**
     * Get monthly savings rate for last N months (for trend display)
     *
     * @param int $months Number of months
     * @return array ['labels' => [...], 'rates' => [...]]
     */
    protected function getMonthlyTrend(int $months = 6): array
    {
        $userId = (int) $this->userId;
        $labels = [];
        $rates = [];
        $incomes = [];
        $expenses = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = strtotime("-{$i} months");
            $m = (int) date('m', $date);
            $y = (int) date('Y', $date);
            $labels[] = date('M', $date);

            $inc = (float) Yii::$app->db->createCommand(
                'SELECT COALESCE(SUM(amount), 0) FROM {{%incomes}}
             WHERE workspace_id = :userId AND MONTH(entry_date) = :month AND YEAR(entry_date) = :year',
                [':userId' => $userId, ':month' => $m, ':year' => $y]
            )->queryScalar();

            $exp = (float) Yii::$app->db->createCommand(
                'SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}}
             WHERE workspace_id = :userId AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year',
                [':userId' => $userId, ':month' => $m, ':year' => $y]
            )->queryScalar();

            $incomes[] = $inc;
            $expenses[] = $exp;
            $rates[] = $inc > 0 ? round((($inc - $exp) / $inc) * 100, 1) : 0;
        }

        return compact('labels', 'rates', 'incomes', 'expenses');
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        $this->registerAssets();

        $savingsRate = $this->_income > 0
            ? round(($this->_balance / $this->_income) * 100, 1)
            : 0;
        $expenseRate = $this->_income > 0
            ? round(($this->_expense / $this->_income) * 100, 1)
            : 0;
        $trend = $this->getMonthlyTrend(6);

        return $this->render('monthly-performance', [
            'widgetId' => $this->_widgetId,
            'title' => Yii::t('app', $this->title),
            'containerClass' => $this->containerClass,
            'currentMonthName' => $this->_currentMonthName,
            'income' => $this->_income,
            'expense' => $this->_expense,
            'balance' => $this->_balance,
            'savingsRate' => $savingsRate,
            'expenseRate' => $expenseRate,
            'hasData' => ($this->_income > 0 || $this->_expense > 0),
            'chartHeight' => $this->chartHeight,
            'trend' => $trend,
        ]);
    }

    /**
     * Register JavaScript assets for the chart
     */
    protected function registerAssets(): void
    {
        $view = $this->getView();
        $chartId = 'performanceDonutChart_' . $this->_widgetId;

        $balance = round($this->_balance);
        $expense = round($this->_expense);
        $colorsJson = Json::encode($this->chartColors);
        $balanceLabel = Yii::t('app', 'Balance');
        $expenseLabel = Yii::t('app', 'Expense');
        $totalLabel = Yii::t('app', 'Total');
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
                series: [{$balance}, {$expense}],
                chart: {
                    type: 'donut',
                    height: {$height},
                    fontFamily: 'Inter, -apple-system, sans-serif',
                    toolbar: { show: false }
                },
                labels: ['{$balanceLabel}', '{$expenseLabel}'],
                colors: colors,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '14px',
                                    fontWeight: 500,
                                    color: '#6B7280'
                                },
                                value: {
                                    show: true,
                                    fontSize: '24px',
                                    fontWeight: 600,
                                    color: '#1F2937',
                                    formatter: function(val) {
                                        return parseFloat(val).toLocaleString();
                                    }
                                },
                                total: {
                                    show: true,
                                    label: '{$totalLabel}',
                                    fontSize: '14px',
                                    fontWeight: 400,
                                    color: '#6B7280',
                                    formatter: function(w) {
                                        var total = w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                                        return total.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom',
                    fontSize: '13px',
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 3
                    },
                    itemMargin: {
                        horizontal: 15,
                        vertical: 5
                    },
                    formatter: function(seriesName, opts) {
                        var value = opts.w.globals.series[opts.seriesIndex];
                        return seriesName + ': ' + value.toLocaleString();
                    }
                },
                stroke: {
                    width: 2,
                    colors: ['#fff']
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toLocaleString();
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: { height: 280 },
                        legend: { position: 'bottom' }
                    }
                }]
            };

            new ApexCharts(chartElement, options).render();
        })();
        JS;

        $view->registerJs($js, View::POS_END);
    }

    /**
     * Get balance value
     *
     * @return float
     */
    public function getBalance(): float
    {
        return $this->_balance;
    }

    /**
     * Get expense value
     *
     * @return float
     */
    public function getExpense(): float
    {
        return $this->_expense;
    }
}
