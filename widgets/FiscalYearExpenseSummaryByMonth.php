<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\widgets;

use app\models\ExpenseCategory;
use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\db\Query;
use yii\helpers\Html;
use yii\web\View;
use DateTime;

/**
 * FiscalYearExpenseSummaryByMonth Widget
 *
 * Displays a monthly breakdown of expenses by category for a fiscal year.
 * Supports category filtering, Excel export, and parent-child category hierarchy.
 *
 * ## Features
 *
 * - Monthly expense breakdown in tabular format
 * - Category filtering with checkboxes
 * - Excel export functionality
 * - Parent-child category hierarchy support
 * - Responsive horizontal scrolling
 * - Grand total calculations
 *
 * ## Usage
 *
 * ```php
 * <?= FiscalYearExpenseSummaryByMonth::widget([
 *     'fiscalStartDate' => '2024-07-01',
 *     'fiscalEndDate' => '2025-06-30',
 *     'fiscalYearLabel' => 'FY 2024-25',
 * ]) ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class FiscalYearExpenseSummaryByMonth extends Widget
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
    public $title = 'Fiscal Year Expense Summary';

    /** @var string Widget subtitle */
    public $subtitle = 'Monthly breakdown by category';

    /** @var string Widget container CSS class */
    public $containerClass = '';

    /** @var bool Enable Excel export */
    public $enableExport = true;

    /** @var bool Enable category filtering */
    public $enableFiltering = true;

    /** @var string Expense table name */
    public $expenseTable = '{{%expenses}}';

    /** @var string Expense category table name */
    public $expenseCategoryTable = '{{%expense_categories}}';

    /** @var string Unique widget ID */
    private $_widgetId;

    /** @var array Month range data */
    private $_months = [];

    /** @var array Category data */
    private $_categories = [];

    /** @var array Ordered categories for display */
    private $_orderedCategories = [];

    /** @var array Pivot data (month => category => amount) */
    private $_pivot = [];

    /** @var array Category totals */
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
            $this->userId = Yii::$app->user->id;
        }

        if (empty($this->fiscalYearLabel)) {
            $this->fiscalYearLabel = $this->generateFiscalYearLabel();
        }

        $this->loadData();

        // Handle export request
        if ($this->enableExport && Yii::$app->request->get('export') == 1) {
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
        $this->loadCategories();
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
     * Load expense categories with hierarchy
     */
    protected function loadCategories(): void
    {
        $rawCategories = (new Query())
            ->select(['id', 'name', 'parent_id'])
            ->from($this->expenseCategoryTable)
            ->where(['user_id' => $this->userId])
            ->all();

        $childrenMap = [];

        foreach ($rawCategories as $cat) {
            if ($cat['parent_id'] === null) {
                $this->_categories[$cat['id']] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                    'children' => []
                ];
            } else {
                $childrenMap[$cat['parent_id']][] = $cat['id'];
            }
        }

        foreach ($this->_categories as $id => $cat) {
            $this->_categories[$id]['children'] = $childrenMap[$id] ?? [];
        }

        // Build ordered categories for display
        foreach ($this->_categories as $parentId => $cat) {
            $this->_orderedCategories[$parentId] = $cat['name'];

            foreach ($cat['children'] as $childId) {
                foreach ($rawCategories as $c) {
                    if ($c['id'] === $childId) {
                        $suffix = ($c['name'] === $cat['name']) ? ' (sub)' : '';
                        $this->_orderedCategories[$childId] = '└ ' . $c['name'] . $suffix;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Load expense data and build pivot table
     */
    protected function loadExpenses(): void
    {
        $rawData = (new Query())
            ->select([
                "DATE_FORMAT(expense_date, '%Y-%m') AS ym",
                'expense_category_id',
                'SUM(CAST(amount AS DECIMAL(12,2))) AS total'
            ])
            ->from($this->expenseTable)
            ->where([
                'and',
                ['user_id' => $this->userId],
                ['BETWEEN', 'expense_date', $this->fiscalStartDate, $this->fiscalEndDate]
            ])
            ->groupBy(['ym', 'expense_category_id'])
            ->all();

        // Build pivot table
        foreach ($rawData as $row) {
            $ym = $row['ym'];
            $catId = $row['expense_category_id'];
            $amount = (float) $row['total'];

            $this->_pivot[$ym][$catId] = $amount;

            if (!isset($this->_totals[$catId])) {
                $this->_totals[$catId] = 0;
            }
            $this->_totals[$catId] += $amount;
        }

        // Calculate parent sums from children
        foreach ($this->_categories as $parentId => $cat) {
            foreach ($this->_months as $ym => $label) {
                if (!isset($this->_pivot[$ym][$parentId])) {
                    $this->_pivot[$ym][$parentId] = 0;
                }

                if (!empty($cat['children'])) {
                    foreach ($cat['children'] as $childId) {
                        $this->_pivot[$ym][$parentId] += $this->_pivot[$ym][$childId] ?? 0;
                    }
                }
            }

            // Recalculate parent total
            $this->_totals[$parentId] = array_sum(array_column($this->_pivot, $parentId));
        }
    }

    /**
     * Calculate grand total
     */
    protected function calculateTotals(): void
    {
        foreach ($this->_months as $ym => $label) {
            foreach (array_keys($this->_categories) as $parentId) {
                $this->_grandTotal += $this->_pivot[$ym][$parentId] ?? 0;
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

        return $this->render('fiscal-year-expense-summary-by-month', [
            'widgetId' => $this->_widgetId,
            'title' => Yii::t('app', $this->title),
            'subtitle' => Yii::t('app', $this->subtitle),
            'fiscalYearLabel' => $this->fiscalYearLabel,
            'containerClass' => $this->containerClass,
            'months' => $this->_months,
            'categories' => $this->_orderedCategories,
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
        return $url . $separator . 'export=1';
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

                // Checkbox change handler
                container.querySelectorAll('.category-checkbox').forEach(function(cb) {
                    cb.addEventListener('change', updateCategoryVisibility);
                });

                // Select All
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

                // Deselect All
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

                // Initial state
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
        $flatCategoryIds = [];

        foreach ($this->_categories as $parentId => $cat) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValueExplicit($colLetter . '1', $cat['name'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $flatCategoryIds[] = $parentId;

            if (!empty($cat['children'])) {
                foreach ($cat['children'] as $childId) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
                    $childName = ExpenseCategory::find()
                        ->select(['name'])
                        ->where(['id' => $childId])
                        ->scalar();
                    $cleanedName = preg_replace('/[└├│─┬┼┤┐┘┌└]/u', '', $childName ?? 'Unknown');
                    $sheet->setCellValueExplicit($colLetter . '1', '   └ ' . $cleanedName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $flatCategoryIds[] = $childId;
                }
            }
        }

        // Data rows
        $rowIndex = 2;
        foreach ($this->_months as $ym => $label) {
            $sheet->setCellValue("A{$rowIndex}", $label);
            $colIndex = 2;
            foreach ($flatCategoryIds as $catId) {
                $value = $this->_pivot[$ym][$catId] ?? 0;
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
        foreach ($flatCategoryIds as $catId) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValue("{$colLetter}{$rowIndex}", $this->_totals[$catId] ?? 0);
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

        // Auto-size columns
        $sheet->getColumnDimension('A')->setWidth(18);
        for ($i = 2; $i < $colIndex; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $value = $sheet->getCell($col . '1')->getValue();
            $width = max(10, min(50, mb_strlen((string) $value) + 5));
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Output
        $filename = 'Expenses-' . str_replace(' ', '-', $this->fiscalYearLabel) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
