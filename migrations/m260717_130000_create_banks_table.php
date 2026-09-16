<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Banks Table
 *
 * Workspace-scoped list of banks, used to tag Card / Bank Transfer expenses
 * with the bank they went through.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class m260717_130000_create_banks_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%banks}}';

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
            'workspace_id' => $this->integer()->notNull(),
            'name' => $this->string(191)->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
            'created_by' => $this->integer()->defaultValue(null),
            'updated_by' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // One bank name per workspace.
        $this->createIndex(
            'idx-banks-workspace_id-name',
            $this->tableName,
            ['workspace_id', 'name'],
            true
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropTable($this->tableName);

        return true;
    }
}
