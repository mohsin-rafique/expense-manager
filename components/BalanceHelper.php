<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;

/**
 * BalanceHelper provides balance calculation utilities for computing
 * a user's net financial position (total income minus total expenses).
 *
 * @package app\components
 */
class BalanceHelper
{
    /**
     * Returns the net balance (total income minus total expenses) for a user.
     *
     * @param int $userId The ID of the user.
     * @return float Net balance; positive means surplus, negative means deficit.
     */
    public static function getBalance(int $userId): float
    {
        // Get Total Incomes
        $totalIncomes = Yii::$app->db->createCommand('SELECT SUM(amount) FROM {{%incomes}} WHERE user_id = :user_id')
            ->bindValue(':user_id', $userId)
            ->queryScalar();

        // Get Total Expenses
        $totalExpenses = Yii::$app->db->createCommand('SELECT SUM(amount) FROM {{%expenses}} WHERE user_id = :user_id')
            ->bindValue(':user_id', $userId)
            ->queryScalar();

        // Calculate Total Profit/Loss
        return (float)$totalIncomes - (float)$totalExpenses;
    }
}
