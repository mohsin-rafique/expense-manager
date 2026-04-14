<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Incomes Table
 *
 * Creates the incomes table for tracking income transactions:
 * - Transaction details (date, amount, reference)
 * - Category association
 * - File attachments (receipts, invoices)
 * - Audit trail (created_by, updated_by)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260101_184731_incomes_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%incomes}}';

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
            'income_category_id' => $this->integer()->notNull(),
            'entry_date' => $this->date()->notNull(),
            'reference' => $this->string(191)->defaultValue(null),
            'description' => $this->text()->defaultValue(null),
            'amount' => $this->decimal(10, 2)->notNull(),
            'filename' => $this->string(96)->defaultValue(null),
            'filepath' => $this->string(191)->defaultValue(null),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
            'created_by' => $this->integer()->defaultValue(null),
            'updated_by' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // Index for user's incomes
        $this->createIndex(
            'idx-incomes-user_id',
            $this->tableName,
            'user_id'
        );

        // Index for category filtering
        $this->createIndex(
            'idx-incomes-income_category_id',
            $this->tableName,
            'income_category_id'
        );

        // Index for date range queries
        $this->createIndex(
            'idx-incomes-entry_date',
            $this->tableName,
            'entry_date'
        );

        // Composite index for user + date (common query pattern)
        $this->createIndex(
            'idx-incomes-user_id-entry_date',
            $this->tableName,
            ['user_id', 'entry_date']
        );

        // Index for reference search
        $this->createIndex(
            'idx-incomes-reference',
            $this->tableName,
            'reference'
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-incomes-user_id',
            $this->tableName,
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key to income_categories table
        $this->addForeignKey(
            'fk-incomes-income_category_id',
            $this->tableName,
            'income_category_id',
            '{{%income_categories}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // Foreign key for created_by
        $this->addForeignKey(
            'fk-incomes-created_by',
            $this->tableName,
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Foreign key for updated_by
        $this->addForeignKey(
            'fk-incomes-updated_by',
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
        $this->dropForeignKey('fk-incomes-updated_by', $this->tableName);
        $this->dropForeignKey('fk-incomes-created_by', $this->tableName);
        $this->dropForeignKey('fk-incomes-income_category_id', $this->tableName);
        $this->dropForeignKey('fk-incomes-user_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
