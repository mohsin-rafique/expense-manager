<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\services;

/**
 * FiscalYearService handles fiscal year calculations and configuration.
 *
 * Pakistan's fiscal year runs from July 1 to June 30.
 * This service generates available fiscal years, determines the current one,
 * and provides lookup by label.
 *
 * Usage:
 * ```php
 * $service = new FiscalYearService();
 * $current = $service->getCurrentFiscalYear();
 * // ['startDate' => '2025-07-01', 'endDate' => '2026-06-30', 'label' => 'FY 2025-26']
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.1.0
 */
class FiscalYearService
{
    /** @var int Month when fiscal year starts (July = 7) */
    private int $fiscalStartMonth;

    /** @var int First year to include in available fiscal years */
    private int $startYear;

    /**
     * @param int $fiscalStartMonth Month when fiscal year starts (default: 7 for July)
     * @param int $startYear First fiscal year to include (default: 2024)
     */
    public function __construct(int $fiscalStartMonth = 7, int $startYear = 2024)
    {
        $this->fiscalStartMonth = $fiscalStartMonth;
        $this->startYear = $startYear;
    }

    /**
     * Returns the current fiscal year based on today's date.
     *
     * If current month is before the fiscal start month, the fiscal year
     * started in the previous calendar year.
     *
     * @return array{startDate: string, endDate: string, label: string}
     */
    public function getCurrentFiscalYear(): array
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        $fyStartYear = ($currentMonth < $this->fiscalStartMonth)
            ? $currentYear - 1
            : $currentYear;

        return $this->buildFiscalYear($fyStartYear);
    }

    /**
     * Returns all available fiscal years from startYear to current.
     *
     * @return array[] Array of fiscal year configurations, newest first
     */
    public function getAvailableFiscalYears(): array
    {
        $current = $this->getCurrentFiscalYear();
        $currentStartYear = (int) substr($current['startDate'], 0, 4);

        $years = [];
        for ($y = $this->startYear; $y <= $currentStartYear; $y++) {
            $years[] = $this->buildFiscalYear($y);
        }

        return array_reverse($years); // Newest first
    }

    /**
     * Finds a fiscal year by its label string.
     *
     * @param string $label Fiscal year label (e.g., 'FY 2024-25')
     * @return array|null Fiscal year config or null if not found
     */
    public function getFiscalYearByLabel(string $label): ?array
    {
        foreach ($this->getAvailableFiscalYears() as $fy) {
            if ($fy['label'] === $label) {
                return $fy;
            }
        }

        return null;
    }

    /**
     * Builds a fiscal year configuration array for a given start year.
     *
     * @param int $year The calendar year the fiscal year starts in
     * @return array{startDate: string, endDate: string, label: string}
     */
    private function buildFiscalYear(int $year): array
    {
        $startMonth = str_pad($this->fiscalStartMonth, 2, '0', STR_PAD_LEFT);
        $endMonth = str_pad($this->fiscalStartMonth - 1, 2, '0', STR_PAD_LEFT);
        $endYear = $year + 1;

        // Last day of the month before fiscal start
        $endDate = date('Y-m-t', strtotime("{$endYear}-{$endMonth}-01"));

        return [
            'startDate' => "{$year}-{$startMonth}-01",
            'endDate' => $endDate,
            'label' => "FY {$year}-" . substr((string) $endYear, -2),
        ];
    }
}
