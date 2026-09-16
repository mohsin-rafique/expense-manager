<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\helpers;

/**
 * Presentation helpers shared by the fiscal-year expense summary widget views.
 *
 * These were previously duplicated as file-scoped functions inside each widget
 * view, which risked a "Cannot redeclare" fatal when two summary widgets render
 * on the same page. Centralising them here gives a single definition with no
 * global functions and no render-order dependency.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class FiscalSummaryFormat
{
    /**
     * Returns the heat map CSS class (heat-1 to heat-5) for a value relative to
     * the maximum value in its column. Returns an empty string for non-positive
     * values or a non-positive max.
     *
     * @param float $value Cell value
     * @param float $max Maximum value in the column
     * @return string Heat map class, or '' when no heat should be applied
     */
    public static function heatClass(float $value, float $max): string
    {
        if ($value <= 0 || $max <= 0) {
            return '';
        }

        $ratio = $value / $max;

        if ($ratio >= 0.8) {
            return 'heat-5';
        }
        if ($ratio >= 0.6) {
            return 'heat-4';
        }
        if ($ratio >= 0.4) {
            return 'heat-3';
        }
        if ($ratio >= 0.2) {
            return 'heat-2';
        }

        return 'heat-1';
    }

    /**
     * Returns a month's status relative to the current month.
     *
     * @param string $ym Month key in Y-m format (e.g. '2025-03')
     * @param string $currentYm Current month key in Y-m format
     * @return string One of 'current', 'past', or 'future'
     */
    public static function monthStatus(string $ym, string $currentYm): string
    {
        if ($ym === $currentYm) {
            return 'current';
        }

        return ($ym < $currentYm) ? 'past' : 'future';
    }
}
