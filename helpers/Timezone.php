<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\helpers;

/**
 * Timezone helper.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 */
class Timezone
{
    /**
     * Get all of the time zones with the offsets sorted by their offset
     *
     * @return array
     */
    public static function getAll()
    {
        $timeZones = [];
        $timeZoneIdentifiers = \DateTimeZone::listIdentifiers();

        foreach ($timeZoneIdentifiers as $timeZone) {
            $date = new \DateTime('now', new \DateTimeZone($timeZone));
            $offset = $date->getOffset();
            $tz = ($offset > 0 ? '+' : '-') . gmdate('H:i', abs($offset));
            $timeZones[] = [
                'timezone' => $timeZone,
                'name' => "{$timeZone} (UTC {$tz})",
                'offset' => $offset
            ];
        }

        \yii\helpers\ArrayHelper::multisort($timeZones, 'offset', SORT_DESC, SORT_NUMERIC);

        return $timeZones;
    }

    /**
     * Format the fiscal year based on the given current year.
     *
     * @param int $currentYear The current year to base the fiscal year on.
     * @return string The formatted fiscal year.
     */
    public static function formatFiscalYear($currentYear)
    {
        // Calculate the start and end years for the fiscal year
        $fiscal_start_year = $currentYear - 1;
        $fiscal_end_year = $currentYear;

        // Format the fiscal year dates
        $fiscal_start_date = "July 1, {$fiscal_start_year}";
        $fiscal_end_date = "June 30, {$fiscal_end_year}";

        // Return the formatted fiscal year string
        return "{$fiscal_start_date} - {$fiscal_end_date}";
    }
}
