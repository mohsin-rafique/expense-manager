<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Expenses Table
 *
 * Creates the expenses table for tracking expense transactions:
 * - Transaction details (date, amount, reference)
 * - Category association
 * - Payment method tracking
 * - File attachments (receipts, invoices)
 * - Audit trail (created_by, updated_by)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260101_185010_expenses_table extends Migration
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
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'expense_category_id' => $this->integer()->notNull(),
            'expense_date' => $this->date()->notNull(),
            'description' => $this->text()->defaultValue(null),
            'amount' => $this->decimal(10, 2)->notNull(),
            'filename' => $this->string(96)->defaultValue(null),
            'filepath' => $this->string(191)->defaultValue(null),
            'payment_method' => "ENUM('Card', 'Cash', 'Bank') NOT NULL DEFAULT 'Card'",
            'reference' => $this->string(191)->defaultValue(null),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
            'created_by' => $this->integer()->defaultValue(null),
            'updated_by' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // Index for user's expenses
        $this->createIndex(
            'idx-expenses-user_id',
            $this->tableName,
            'user_id'
        );

        // Index for category filtering
        $this->createIndex(
            'idx-expenses-expense_category_id',
            $this->tableName,
            'expense_category_id'
        );

        // Index for date range queries
        $this->createIndex(
            'idx-expenses-expense_date',
            $this->tableName,
            'expense_date'
        );

        // Composite index for user + date (common query pattern)
        $this->createIndex(
            'idx-expenses-user_id-expense_date',
            $this->tableName,
            ['user_id', 'expense_date']
        );

        // Index for payment method filtering
        $this->createIndex(
            'idx-expenses-payment_method',
            $this->tableName,
            'payment_method'
        );

        // Index for reference search
        $this->createIndex(
            'idx-expenses-reference',
            $this->tableName,
            'reference'
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-expenses-user_id',
            $this->tableName,
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key to expense_categories table
        $this->addForeignKey(
            'fk-expenses-expense_category_id',
            $this->tableName,
            'expense_category_id',
            '{{%expense_categories}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // Foreign key for created_by
        $this->addForeignKey(
            'fk-expenses-created_by',
            $this->tableName,
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Foreign key for updated_by
        $this->addForeignKey(
            'fk-expenses-updated_by',
            $this->tableName,
            'updated_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropForeignKey('fk-expenses-updated_by', $this->tableName);
        $this->dropForeignKey('fk-expenses-created_by', $this->tableName);
        $this->dropForeignKey('fk-expenses-expense_category_id', $this->tableName);
        $this->dropForeignKey('fk-expenses-user_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
