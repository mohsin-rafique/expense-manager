<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Budgets Table
 *
 * Creates the budgets table for the Budget Management module. A budget sets a
 * spending cap (or income target) for a single category over a recurring
 * period (monthly, yearly, or fiscal year) with optional alert thresholds.
 *
 * - `category_type` distinguishes expense vs income category budgets.
 * - `category_id` points to either {{%expense_categories}} or
 *   {{%income_categories}} depending on `category_type`.
 * - `period_type` controls the rolling window used to compute spending.
 * - `alert_threshold` is the percentage (1-100) at which a warning is raised.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260608_100000_create_budgets_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%budgets}}';

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

            // Category target
            'category_type' => "ENUM('expense', 'income') NOT NULL DEFAULT 'expense'",
            'category_id' => $this->integer()->notNull(),

            // Budget definition
            'period_type' => "ENUM('monthly', 'yearly', 'fiscal') NOT NULL DEFAULT 'monthly'",
            'amount' => $this->decimal(12, 2)->notNull(),

            // Alerting
            'alert_threshold' => $this->integer()->notNull()->defaultValue(80),
            'email_alerts' => $this->boolean()->notNull()->defaultValue(false),

            // Meta
            'note' => $this->string(191)->defaultValue(null),
            'status' => $this->boolean()->notNull()->defaultValue(true),

            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
            'created_by' => $this->integer()->defaultValue(null),
            'updated_by' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // Index for user's budgets
        $this->createIndex('idx-budgets-user_id', $this->tableName, 'user_id');

        // Index for category lookups (used when checking budgets after a transaction)
        $this->createIndex('idx-budgets-category', $this->tableName, ['category_type', 'category_id']);

        // One budget per category + period per user
        $this->createIndex(
            'idx-budgets-unique',
            $this->tableName,
            ['user_id', 'category_type', 'category_id', 'period_type'],
            true
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-budgets-user_id',
            $this->tableName,
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropForeignKey('fk-budgets-user_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
