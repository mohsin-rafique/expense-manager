<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use app\models\ExpenseCategory;
use Yii;
use yii\base\Widget;
use yii\db\Query;
use yii\web\View;
use DateTime;

/**
 * FiscalYearExpenseSummaryByFbrCategory Widget
 *
 * Displays a monthly breakdown of expenses grouped by FBR (Federal Board of
 * Revenue) tax category for a fiscal year. Intended for Pakistan users, where
 * expenses can be mapped to FBR personal expense categories used in tax filing.
 *
 * ## Features
 *
 * - Monthly expense breakdown by FBR tax category in tabular format
 * - FBR category filtering with checkboxes
 * - Excel export functionality
 * - Heat map visualization
 * - Grand total calculations
 *
 * ## Usage
 *
 * ```php
 * <?= FiscalYearExpenseSummaryByFbrCategory::widget([
 *     'fiscalStartDate' => '2024-07-01',
 *     'fiscalEndDate' => '2025-06-30',
 *     'fiscalYearLabel' => 'FY 2024-25',
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class FiscalYearExpenseSummaryByFbrCategory extends Widget
{
    /** @var string Fiscal year start date (Y-m-d format) */
    public $fiscalStartDate = '2024-07-01';

    /** @var string Fiscal year end date (Y-m-d format) */
    public $fiscalEndDate = '2025-06-30';

    /** @var string Fiscal year display label */
    public $fiscalYearLabel = '';

    /** @var int|null User ID (defaults to current logged-in user) */
    public $userId;

    /** @var string Widget title */
    public $title = 'Fiscal Year Expense Summary by FBR Tax Category';

    /** @var string Widget subtitle */
    public $subtitle = 'Monthly breakdown by FBR tax category';

    /** @var string Widget container CSS class */
    public $containerClass = '';

    /** @var bool Enable Excel export */
    public $enableExport = true;

    /** @var bool Enable FBR category filtering */
    public $enableFiltering = true;

    /** @var string Expense table name */
    public $expenseTable = '{{%expenses}}';

    /** @var string Query parameter that triggers the export for this widget */
    public $exportParam = 'export_fbr';

    /** @var string Unique widget ID */
    private $_widgetId;

    /** @var array Month range data (ym => label) */
    private $_months = [];

    /** @var array FBR categories (code => label) */
    private $_categories = [];

    /** @var array Pivot data (month => fbrCode => amount) */
    private $_pivot = [];

    /** @var array Category totals (fbrCode => amount) */
    private $_totals = [];

    /** @var float Grand total */
    private $_grandTotal = 0;

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

        if (empty($this->fiscalYearLabel)) {
            $this->fiscalYearLabel = $this->generateFiscalYearLabel();
        }

        $this->_categories = ExpenseCategory::getFbrCategories();

        $this->loadData();

        // Handle export request
        if ($this->enableExport && Yii::$app->request->get($this->exportParam) == 1) {
            $this->exportToExcel();
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
     * Load all required data
     */
    protected function loadData(): void
    {
        if (!$this->userId) {
            return;
        }

        $this->loadMonths();
        $this->loadExpenses();
        $this->calculateTotals();
    }

    /**
     * Generate month range
     */
    protected function loadMonths(): void
    {
        $start = new DateTime($this->fiscalStartDate);
        $end = new DateTime($this->fiscalEndDate);
        $end->modify('first day of this month');

        while ($start <= $end) {
            $key = $start->format('Y-m');
            $this->_months[$key] = $start->format('M Y');
            $start->modify('+1 month');
        }
    }

    /**
     * Load expense data grouped by month and FBR category, then build pivot table
     */
    protected function loadExpenses(): void
    {
        $rawData = (new Query())
            ->select([
                "DATE_FORMAT(expense_date, '%Y-%m') AS ym",
                'fbr_category',
                'SUM(CAST(amount AS DECIMAL(12,2))) AS total'
            ])
            ->from($this->expenseTable)
            ->where([
                'and',
                ['workspace_id' => $this->userId],
                ['BETWEEN', 'expense_date', $this->fiscalStartDate, $this->fiscalEndDate],
                ['not', ['fbr_category' => null]],
                ['<>', 'fbr_category', ''],
            ])
            ->groupBy(['ym', 'fbr_category'])
            ->all();

        foreach ($rawData as $row) {
            $ym = $row['ym'];
            $code = $row['fbr_category'];

            // Skip codes no longer present in the FBR category map
            if (!isset($this->_categories[$code])) {
                continue;
            }

            $amount = (float) $row['total'];

            $this->_pivot[$ym][$code] = $amount;

            if (!isset($this->_totals[$code])) {
                $this->_totals[$code] = 0;
            }
            $this->_totals[$code] += $amount;
        }
    }

    /**
     * Calculate grand total
     */
    protected function calculateTotals(): void
    {
        foreach ($this->_months as $ym => $label) {
            foreach (array_keys($this->_categories) as $code) {
                $this->_grandTotal += $this->_pivot[$ym][$code] ?? 0;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        if (!$this->userId) {
            return '';
        }

        $this->registerAssets();

        return $this->render('fiscal-year-expense-summary-by-fbr-category', [
            'widgetId' => $this->_widgetId,
            'title' => Yii::t('app', $this->title),
            'subtitle' => Yii::t('app', $this->subtitle),
            'fiscalYearLabel' => $this->fiscalYearLabel,
            'containerClass' => $this->containerClass,
            'months' => $this->_months,
            'categories' => $this->_categories,
            'pivot' => $this->_pivot,
            'totals' => $this->_totals,
            'grandTotal' => $this->_grandTotal,
            'enableExport' => $this->enableExport,
            'enableFiltering' => $this->enableFiltering,
            'exportUrl' => $this->getExportUrl(),
        ]);
    }

    /**
     * Get export URL
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
     * Register JavaScript and CSS assets
     */
    protected function registerAssets(): void
    {
        $view = $this->getView();
        $widgetId = $this->_widgetId;

        if ($this->enableFiltering) {
            $js = <<<JS
            (function() {
                'use strict';

                var container = document.getElementById('{$widgetId}');
                if (!container) return;

                function updateCategoryVisibility() {
                    var checkboxes = container.querySelectorAll('.category-checkbox:checked');
                    var checkedIds = Array.from(checkboxes).map(function(cb) { return cb.value; });

                    container.querySelectorAll('.category-col').forEach(function(col) {
                        var catId = col.dataset.catId;
                        col.style.display = checkedIds.includes(catId) ? '' : 'none';
                    });
                }

                container.querySelectorAll('.category-checkbox').forEach(function(cb) {
                    cb.addEventListener('change', updateCategoryVisibility);
                });

                var selectAllBtn = container.querySelector('#selectAllCategories-{$widgetId}');
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        container.querySelectorAll('.category-checkbox').forEach(function(cb) {
                            cb.checked = true;
                        });
                        updateCategoryVisibility();
                    });
                }

                var deselectAllBtn = container.querySelector('#deselectAllCategories-{$widgetId}');
                if (deselectAllBtn) {
                    deselectAllBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        container.querySelectorAll('.category-checkbox').forEach(function(cb) {
                            cb.checked = false;
                        });
                        updateCategoryVisibility();
                    });
                }

                updateCategoryVisibility();
            })();
            JS;

            $view->registerJs($js, View::POS_END);
        }

        $css = <<<CSS
        #{$widgetId} .expense-summary-table th,
        #{$widgetId} .expense-summary-table td {
            white-space: nowrap;
            padding: 0.5rem 1rem;
        }
        #{$widgetId} .expense-summary-table {
            table-layout: auto;
            width: auto;
        }
        CSS;

        $view->registerCss($css);
    }

    /**
     * Export data to Excel
     */
    protected function exportToExcel(): void
    {
        // Disable debug module if present
        if (Yii::$app->hasModule('debug')) {
            Yii::$app->getModule('debug')->instance = null;
        }

        // Clear output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Build headers
        $sheet->setCellValue('A1', 'Month');
        $colIndex = 2;
        $codes = array_keys($this->_categories);

        foreach ($this->_categories as $code => $label) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValueExplicit($colLetter . '1', $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        // Data rows
        $rowIndex = 2;
        foreach ($this->_months as $ym => $label) {
            $sheet->setCellValue("A{$rowIndex}", $label);
            $colIndex = 2;
            foreach ($codes as $code) {
                $value = $this->_pivot[$ym][$code] ?? 0;
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
                if ($value == 0) {
                    $sheet->setCellValueExplicit("{$colLetter}{$rowIndex}", '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue("{$colLetter}{$rowIndex}", $value);
                }
            }
            $rowIndex++;
        }

        // Totals row
        $sheet->setCellValue("A{$rowIndex}", 'Category Totals');
        $colIndex = 2;
        foreach ($codes as $code) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValue("{$colLetter}{$rowIndex}", $this->_totals[$code] ?? 0);
        }
        $rowIndex++;

        // Grand Total row
        $sheet->setCellValue("A{$rowIndex}", 'Grand Total');
        $sheet->setCellValue("B{$rowIndex}", $this->_grandTotal);

        // Styling
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex - 1);

        // Header style
        $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColLetter}1")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9EBEC');

        // Totals row style
        $totalsRowIndex = $rowIndex - 1;
        $sheet->getStyle("A{$totalsRowIndex}:{$lastColLetter}{$totalsRowIndex}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9EBEC');

        // Grand total row style
        $sheet->getStyle("A{$rowIndex}:{$lastColLetter}{$rowIndex}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('CEF0EB');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(18);
        for ($i = 2; $i < $colIndex; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $value = $sheet->getCell($col . '1')->getValue();
            $width = max(10, min(50, mb_strlen((string) $value) + 5));
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Output
        $filename = 'Expenses-FBR-' . str_replace(' ', '-', $this->fiscalYearLabel) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
