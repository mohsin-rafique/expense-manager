<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\db\Expression;
use app\models\Income;
use app\models\Expense;

/**
 * FiscalYearIncomeExpenseWidget displays a side-by-side monthly comparison
 * of income vs expenses for the selected fiscal year.
 *
 * Renders a grouped bar chart (ApexCharts) along with summary totals
 * and a net savings indicator for the fiscal period.
 *
 * @property string $fiscalStartDate Start date of the fiscal year (Y-m-d)
 * @property string $fiscalEndDate End date of the fiscal year (Y-m-d)
 * @property string $fiscalYearLabel Display label for the fiscal year (e.g. "FY 2025-26")
 * @property string $containerClass Additional CSS classes for the outer container
 * @property bool $showTrendIndicators Whether to display trend arrows on totals
 * @property string $currencyCode ISO currency code for formatting
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class FiscalYearIncomeExpenseWidget extends Widget
{
    /**
     * @var string Start date of the fiscal year (Y-m-d format)
     */
    public $fiscalStartDate;

    /**
     * @var string End date of the fiscal year (Y-m-d format)
     */
    public $fiscalEndDate;

    /**
     * @var string Display label for the fiscal year
     */
    public $fiscalYearLabel = '';

    /**
     * @var string Additional CSS classes for the container element
     */
    public $containerClass = '';

    /**
     * @var bool Whether to show trend indicators on summary cards
     */
    public $showTrendIndicators = true;

    /**
     * @var string ISO 4217 currency code
     */
    public $currencyCode = 'USD';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (empty($this->fiscalStartDate) || empty($this->fiscalEndDate)) {
            throw new \yii\base\InvalidConfigException(
                'Both "fiscalStartDate" and "fiscalEndDate" are required.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $data = $this->prepareData();

        return $this->render('fiscal-year-income-expense', [
            'monthlyData' => $data['monthly'],
            'totals' => $data['totals'],
            'fiscalYearLabel' => $this->fiscalYearLabel,
            'containerClass' => $this->containerClass,
            'showTrendIndicators' => $this->showTrendIndicators,
            'currencyCode' => $this->currencyCode,
            'chartId' => $this->getId() . '-chart',
        ]);
    }

    /**
     * Prepares monthly income/expense data for the fiscal year.
     *
     * Queries the database for aggregated monthly totals, then fills
     * in any missing months with zeroes to ensure a complete series.
     *
     * @return array{monthly: array, totals: array} Structured data for the view
     */
    protected function prepareData(): array
    {
        $incomeByMonth = $this->getMonthlyIncome();
        $expenseByMonth = $this->getMonthlyExpenses();

        $months = $this->generateMonthRange();
        $monthly = [];
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($months as $monthKey => $monthLabel) {
            $income = $incomeByMonth[$monthKey] ?? 0;
            $expense = $expenseByMonth[$monthKey] ?? 0;

            $monthly[] = [
                'month' => $monthLabel,
                'monthKey' => $monthKey,
                'income' => round((float) $income, 2),
                'expense' => round((float) $expense, 2),
                'net' => round((float) $income - (float) $expense, 2),
            ];

            $totalIncome += $income;
            $totalExpense += $expense;
        }

        $totalIncome = round($totalIncome, 2);
        $totalExpense = round($totalExpense, 2);
        $netSavings = round($totalIncome - $totalExpense, 2);

        return [
            'monthly' => $monthly,
            'totals' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'net' => $netSavings,
                'savingsRate' => $totalIncome > 0
                    ? round(($netSavings / $totalIncome) * 100, 1)
                    : 0,
            ],
        ];
    }

    /**
     * Retrieves monthly income aggregated by year-month.
     *
     * @return array Associative array keyed by 'Y-m' with summed amounts
     */
    protected function getMonthlyIncome(): array
    {
        $userId = Yii::$app->user->id;

        $rows = Income::find()
            ->select([
                new Expression("DATE_FORMAT(entry_date, '%Y-%m') AS month_key"),
                new Expression('SUM(amount) AS total'),
            ])
            ->where(['between', 'entry_date', $this->fiscalStartDate, $this->fiscalEndDate])
            ->andWhere(['user_id' => $userId])
            ->groupBy([new Expression("DATE_FORMAT(entry_date, '%Y-%m')")])
            ->orderBy(new Expression('month_key ASC'))
            ->asArray()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['month_key']] = $row['total'];
        }

        return $result;
    }

    /**
     * Retrieves monthly expenses aggregated by year-month.
     *
     * @return array Associative array keyed by 'Y-m' with summed amounts
     */
    protected function getMonthlyExpenses(): array
    {
        $userId = Yii::$app->user->id;

        $rows = Expense::find()
            ->select([
                new Expression("DATE_FORMAT(expense_date, '%Y-%m') AS month_key"),
                new Expression('SUM(amount) AS total'),
            ])
            ->where(['between', 'expense_date', $this->fiscalStartDate, $this->fiscalEndDate])
            ->andWhere(['user_id' => $userId])
            ->groupBy([new Expression("DATE_FORMAT(expense_date, '%Y-%m')")])
            ->orderBy(new Expression('month_key ASC'))
            ->asArray()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['month_key']] = $row['total'];
        }

        return $result;
    }

    /**
     * Escapes a string for safe use inside JavaScript single-quoted strings.
     *
     * @param string $str The string to escape
     * @return string The escaped string
     */
    public function escapeJs(string $str): string
    {
        return strtr($str, [
            '\\' => '\\\\',
            "'" => "\\'",
            "\n" => '\\n',
            "\r" => '\\r',
        ]);
    }

    /**
     * Generates an ordered list of all months within the fiscal year range.
     *
     * @return array Associative array of 'Y-m' => 'Mon YYYY' labels
     */
    protected function generateMonthRange(): array
    {
        $months = [];
        $start = new \DateTime($this->fiscalStartDate);
        $end = new \DateTime($this->fiscalEndDate);

        // Ensure we include the end month
        $end->modify('first day of this month');

        $current = clone $start;
        $current->modify('first day of this month');

        while ($current <= $end) {
            $key = $current->format('Y-m');
            $months[$key] = $current->format('M Y');
            $current->modify('+1 month');
        }

        return $months;
    }
}
