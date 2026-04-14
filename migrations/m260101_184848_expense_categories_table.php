<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Expense Categories Table
 *
 * Creates the expense categories table with hierarchical structure:
 * - Parent-child relationships (via parent_id)
 * - Category name and description
 * - Visual customization (icon, color)
 * - Status management (active/inactive)
 * - Audit trail (created_by, updated_by)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260101_184848_expense_categories_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%expense_categories}}';

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
            'parent_id' => $this->integer()->defaultValue(null),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(191)->notNull(),
            'description' => $this->text()->defaultValue(null),
            'icon' => $this->string(50)->defaultValue(null),
            'color' => $this->string(20)->defaultValue(null),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
            'created_by' => $this->integer()->defaultValue(null),
            'updated_by' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // Index for hierarchical queries
        $this->createIndex(
            'idx-expense_categories-parent_id',
            $this->tableName,
            'parent_id'
        );

        // Index for user's categories
        $this->createIndex(
            'idx-expense_categories-user_id',
            $this->tableName,
            'user_id'
        );

        // Index for status filtering
        $this->createIndex(
            'idx-expense_categories-status',
            $this->tableName,
            'status'
        );

        // Index for name search
        $this->createIndex(
            'idx-expense_categories-name',
            $this->tableName,
            'name'
        );

        // Composite index for user + parent (tree traversal)
        $this->createIndex(
            'idx-expense_categories-user_id-parent_id',
            $this->tableName,
            ['user_id', 'parent_id']
        );

        // Self-referential foreign key for parent category
        $this->addForeignKey(
            'fk-expense_categories-parent_id',
            $this->tableName,
            'parent_id',
            $this->tableName,
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-expense_categories-user_id',
            $this->tableName,
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key for created_by
        $this->addForeignKey(
            'fk-expense_categories-created_by',
            $this->tableName,
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Foreign key for updated_by
        $this->addForeignKey(
            'fk-expense_categories-updated_by',
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
        $this->dropForeignKey('fk-expense_categories-updated_by', $this->tableName);
        $this->dropForeignKey('fk-expense_categories-created_by', $this->tableName);
        $this->dropForeignKey('fk-expense_categories-user_id', $this->tableName);
        $this->dropForeignKey('fk-expense_categories-parent_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
