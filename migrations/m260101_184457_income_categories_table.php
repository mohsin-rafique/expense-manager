<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Income Categories Table
 *
 * Creates the income categories table for organizing income sources:
 * - Category name and description
 * - Visual customization (icon, color)
 * - Status management (active/inactive)
 * - Audit trail (created_by, updated_by)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260101_184457_income_categories_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%income_categories}}';

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
            'name' => $this->string(96)->notNull(),
            'description' => $this->text()->notNull(),
            'icon' => $this->string(50)->defaultValue(null),
            'color' => $this->string(20)->defaultValue(null),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
            'created_by' => $this->integer()->defaultValue(null),
            'updated_by' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // Index for user's categories
        $this->createIndex(
            'idx-income_categories-user_id',
            $this->tableName,
            'user_id'
        );

        // Index for status filtering
        $this->createIndex(
            'idx-income_categories-status',
            $this->tableName,
            'status'
        );

        // Index for name search
        $this->createIndex(
            'idx-income_categories-name',
            $this->tableName,
            'name'
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-income_categories-user_id',
            $this->tableName,
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key for created_by
        $this->addForeignKey(
            'fk-income_categories-created_by',
            $this->tableName,
            'created_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Foreign key for updated_by
        $this->addForeignKey(
            'fk-income_categories-updated_by',
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
        $this->dropForeignKey('fk-income_categories-updated_by', $this->tableName);
        $this->dropForeignKey('fk-income_categories-created_by', $this->tableName);
        $this->dropForeignKey('fk-income_categories-user_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
