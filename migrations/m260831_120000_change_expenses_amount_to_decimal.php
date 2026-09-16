<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Change Expenses amount to DECIMAL
 *
 * The expenses table was created with a VARCHAR amount column, so MySQL sorted
 * and compared it lexicographically: "10000" ordered before "1020", and an
 * exact-match filter on "1020" missed rows stored as "1020.00". Incomes and
 * budgets already use DECIMAL, so this brings expenses in line with them and
 * makes the Amount column sort numerically.
 *
 * Values are trimmed and stripped of thousands separators before the ALTER so
 * no row is silently coerced to 0 during the conversion.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class m260831_120000_change_expenses_amount_to_decimal extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%expenses}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        // Normalise the stored strings first: strip currency symbols, thousands
        // separators and surrounding whitespace, and default blanks to zero.
        $this->execute("
            UPDATE {$this->tableName}
            SET amount = TRIM(REPLACE(REPLACE(REPLACE(amount, ',', ''), 'Rs', ''), CHAR(160), ' '))
        ");
        $this->execute("
            UPDATE {$this->tableName}
            SET amount = '0'
            WHERE amount IS NULL OR amount = '' OR amount NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
        ");

        $this->alterColumn(
            $this->tableName,
            'amount',
            $this->decimal(12, 2)->notNull()
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->alterColumn(
            $this->tableName,
            'amount',
            $this->string(96)->notNull()
        );

        return true;
    }
}
