<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\viewmodels;

use Yii;
use app\services\FiscalYearService;

/**
 * DashboardViewModel assembles all data required by the dashboard view.
 *
 * Follows the ViewModel pattern to keep the view free of business logic.
 * The controller creates an instance and passes it as a single `$vm` variable.
 *
 * Example usage in controller:
 * ```php
 * public function actionIndex()
 * {
 *     $vm = new DashboardViewModel(Yii::$app->request->get('fy'));
 *     return $this->render('index', compact('vm'));
 * }
 * ```
 *
 * Example usage in view:
 * ```php
 * <?= $vm->currentMonth ?>
 * <?= $vm->fiscalYearConfig['label'] ?>
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.1.0
 */
class DashboardViewModel
{
    // ─── Fiscal Year ─────────────────────────────

    /** @var array{startDate: string, endDate: string, label: string} Active fiscal year */
    public array $fiscalYearConfig;

    /** @var array[] All available fiscal years for the dropdown selector */
    public array $availableFiscalYears;

    /** @var bool Whether user selected a specific fiscal year (vs auto-detected) */
    public bool $isCustomFiscalYear = false;

    // ─── Display Data ────────────────────────────

    /** @var string Currency code from app params (e.g., 'PKR') */
    public string $currencyCode;

    /** @var string Current month formatted for display (e.g., 'March 2026') */
    public string $currentMonth;

    /** @var string Last updated timestamp formatted for display */
    public string $lastUpdated;

    // ─── Widget Config ───────────────────────────

    /** @var int|null Maximum categories to show in chart widgets (null = show all) */
    public ?int $maxCategories;

    /** @var bool Whether to show trend indicators in widgets */
    public bool $showTrendIndicators;

    /** @var bool Whether to enable export functionality */
    public bool $enableExport;

    /** @var bool Whether to enable filtering functionality */
    public bool $enableFiltering;

    /** @var bool Whether to enable previous period comparison */
    public bool $enablePreviousPeriodComparison;

    /** @var string Lifetime calculations start date */
    public string $lifetimeStartDate = '2023-10-01';

    /**
     * Constructs the DashboardViewModel with all data needed by the view.
     *
     * @param string|null $fiscalYearLabel Selected fiscal year label from query param
     * @param array $options Optional overrides:
     *   - maxCategories (int): Max chart categories, default 10
     *   - showTrendIndicators (bool): Show trend arrows, default true
     *   - enableExport (bool): Enable export buttons, default true
     *   - enableFiltering (bool): Enable filter controls, default true
     *   - enablePreviousPeriodComparison (bool): Enable period comparison, default true
     *   - fiscalStartMonth (int): Fiscal year start month, default 7 (July)
     *   - fiscalStartYear (int): First available fiscal year, default 2024
     */
    public function __construct(?string $fiscalYearLabel = null, array $options = [])
    {
        // ── Options with defaults ────────────────
        $this->maxCategories = $options['maxCategories'] ?? 999;
        $this->showTrendIndicators = $options['showTrendIndicators'] ?? true;
        $this->enableExport = $options['enableExport'] ?? true;
        $this->enableFiltering = $options['enableFiltering'] ?? true;
        $this->enablePreviousPeriodComparison = $options['enablePreviousPeriodComparison'] ?? true;

        $fiscalStartMonth = $options['fiscalStartMonth'] ?? 7;
        $fiscalStartYear = $options['fiscalStartYear'] ?? 2024;

        // ── App configuration ────────────────────
        $this->currencyCode = Yii::$app->params['currencyCode'] ?? 'PKR';
        $this->currentMonth = Yii::$app->formatter->asDate(time(), 'MMMM yyyy');
        $this->lastUpdated = Yii::$app->formatter->asDatetime(time(), 'medium');

        // ── Fiscal year resolution ───────────────
        $fiscalService = new FiscalYearService($fiscalStartMonth, $fiscalStartYear);
        $this->availableFiscalYears = $fiscalService->getAvailableFiscalYears();

        if ($fiscalYearLabel !== null) {
            $resolved = $fiscalService->getFiscalYearByLabel($fiscalYearLabel);
            if ($resolved !== null) {
                $this->fiscalYearConfig = $resolved;
                $this->isCustomFiscalYear = true;
            } else {
                $this->fiscalYearConfig = $fiscalService->getCurrentFiscalYear();
            }
        } else {
            $this->fiscalYearConfig = $fiscalService->getCurrentFiscalYear();
        }
    }

    // ─── Convenience Getters ─────────────────────

    /**
     * Returns the fiscal year start date.
     *
     * @return string Date in Y-m-d format
     */
    public function getFiscalStartDate(): string
    {
        return $this->fiscalYearConfig['startDate'];
    }

    /**
     * Returns the fiscal year end date.
     *
     * @return string Date in Y-m-d format
     */
    public function getFiscalEndDate(): string
    {
        return $this->fiscalYearConfig['endDate'];
    }

    /**
     * Returns the fiscal year label.
     *
     * @return string Label like 'FY 2024-25'
     */
    public function getFiscalYearLabel(): string
    {
        return $this->fiscalYearConfig['label'];
    }

    /**
     * Checks if a specific fiscal year is currently selected.
     *
     * @param string $label Fiscal year label to check
     * @return bool True if this is the active fiscal year
     */
    public function isFiscalYearActive(string $label): bool
    {
        return $this->fiscalYearConfig['label'] === $label;
    }
}
