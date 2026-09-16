<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
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
     * @var bool Whether to show the Export button
     */
    public $enableExport = true;

    /**
     * @var string Query parameter that triggers the export. Kept distinct from
     *             the other fiscal-year widgets so only one exports at a time.
     */
    public $exportParam = 'export_income_expense';

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

        // Handle export request. Exits the request, so it must run before any
        // of this widget's markup is produced.
        if ($this->enableExport && Yii::$app->request->get($this->exportParam) == 1) {
            $this->exportToExcel($data);
        }

        return $this->render('fiscal-year-income-expense', [
            'monthlyData' => $data['monthly'],
            'totals' => $data['totals'],
            'fiscalYearLabel' => $this->fiscalYearLabel,
            'containerClass' => $this->containerClass,
            'showTrendIndicators' => $this->showTrendIndicators,
            'currencyCode' => $this->currencyCode,
            'chartId' => $this->getId() . '-chart',
            'enableExport' => $this->enableExport,
            'exportUrl' => $this->getExportUrl(),
        ]);
    }

    /**
     * Builds the URL that triggers this widget's export
     *
     * Appends the export flag to the current URL so the fiscal year and any
     * other dashboard filters in play are preserved.
     *
     * @return string
     */
    protected function getExportUrl(): string
    {
        $url = Yii::$app->request->url;
        $separator = (strpos($url, '?') === false) ? '?' : '&';

        return $url . $separator . $this->exportParam . '=1';
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
        $userId = Yii::$app->workspace->getId();

        $rows = Income::find()
            ->select([
                new Expression("DATE_FORMAT(entry_date, '%Y-%m') AS month_key"),
                new Expression('SUM(amount) AS total'),
            ])
            ->where(['between', 'entry_date', $this->fiscalStartDate, $this->fiscalEndDate])
            ->andWhere(['workspace_id' => $userId])
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
        $userId = Yii::$app->workspace->getId();

        $rows = Expense::find()
            ->select([
                new Expression("DATE_FORMAT(expense_date, '%Y-%m') AS month_key"),
                new Expression('SUM(amount) AS total'),
            ])
            ->where(['between', 'expense_date', $this->fiscalStartDate, $this->fiscalEndDate])
            ->andWhere(['workspace_id' => $userId])
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

    /**
     * Streams the fiscal year comparison as an .xlsx download
     *
     * Writes one row per month with income, expenses and net balance, followed
     * by a totals row and the savings rate. Amounts are written as numbers so
     * the sheet stays usable for further calculation. Terminates the request.
     *
     * @param array $data Output of prepareData()
     * @return void
     */
    protected function exportToExcel(array $data): void
    {
        // Disable debug module if present
        if (Yii::$app->hasModule('debug')) {
            Yii::$app->getModule('debug')->instance = null;
        }

        // Discard markup already produced by earlier dashboard widgets
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Income vs Expenses');

        // Headers
        $sheet->setCellValue('A1', 'Month');
        $sheet->setCellValue('B1', 'Income');
        $sheet->setCellValue('C1', 'Expenses');
        $sheet->setCellValue('D1', 'Net Balance');

        // Data rows
        $rowIndex = 2;
        foreach ($data['monthly'] as $row) {
            $sheet->setCellValueExplicit(
                "A{$rowIndex}",
                $row['month'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValue("B{$rowIndex}", $row['income']);
            $sheet->setCellValue("C{$rowIndex}", $row['expense']);
            $sheet->setCellValue("D{$rowIndex}", $row['net']);
            $rowIndex++;
        }

        // Totals row
        $totalsRowIndex = $rowIndex;
        $sheet->setCellValue("A{$totalsRowIndex}", 'Total');
        $sheet->setCellValue("B{$totalsRowIndex}", $data['totals']['income']);
        $sheet->setCellValue("C{$totalsRowIndex}", $data['totals']['expense']);
        $sheet->setCellValue("D{$totalsRowIndex}", $data['totals']['net']);
        $rowIndex++;

        // Savings rate row
        $sheet->setCellValue("A{$rowIndex}", 'Savings Rate (%)');
        $sheet->setCellValue("B{$rowIndex}", $data['totals']['savingsRate']);

        // Header style
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9EBEC');

        // Totals row style
        $sheet->getStyle("A{$totalsRowIndex}:D{$totalsRowIndex}")->getFont()->setBold(true);
        $sheet->getStyle("A{$totalsRowIndex}:D{$totalsRowIndex}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('CEF0EB');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(18);
        foreach (['B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);
        }

        // Output
        $label = $this->fiscalYearLabel !== '' ? $this->fiscalYearLabel : date('Y');
        $filename = 'Income-vs-Expenses-' . str_replace(' ', '-', $label) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
