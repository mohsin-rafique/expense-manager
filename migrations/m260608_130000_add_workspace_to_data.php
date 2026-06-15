<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;
use yii\db\Query;

/**
 * Migration: Add workspace_id to transactional data & backfill personal workspaces.
 *
 * Adds a workspace_id to the five workspace-owned tables, then for every
 * existing user creates a "Personal" workspace (owner membership) and stamps
 * all of that user's rows with it. After backfilling, workspace_id is made
 * NOT NULL and foreign-keyed.
 *
 * This is behaviour-preserving: each user's data lives in exactly one personal
 * workspace, so scoping by workspace_id yields the same rows as scoping by
 * user_id did before.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260608_130000_add_workspace_to_data extends Migration
{
    /** @var string[] Tables that gain a workspace_id */
    private array $tables = [
        '{{%incomes}}',
        '{{%expenses}}',
        '{{%income_categories}}',
        '{{%expense_categories}}',
        '{{%budgets}}',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        // 1) Add nullable workspace_id to each table
        foreach ($this->tables as $table) {
            $this->addColumn($table, 'workspace_id', $this->integer()->after('user_id'));
        }

        // 2) Create a personal workspace per user and backfill their rows
        $now = time();
        $userIds = (new Query())->select('id')->from('{{%user}}')->column($this->db);

        foreach ($userIds as $userId) {
            $this->insert('{{%workspaces}}', [
                'name' => 'Personal',
                'owner_id' => $userId,
                'is_personal' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $workspaceId = (int) $this->db->getLastInsertID();

            $this->insert('{{%workspace_members}}', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'role' => 'owner',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($this->tables as $table) {
                $this->update($table, ['workspace_id' => $workspaceId], ['user_id' => $userId]);
            }
        }

        // 3) Lock down: NOT NULL + index + FK
        foreach ($this->tables as $table) {
            $this->alterColumn($table, 'workspace_id', $this->integer()->notNull());
            $key = trim($table, '{}%');
            $this->createIndex("idx-{$key}-workspace_id", $table, 'workspace_id');
            $this->addForeignKey("fk-{$key}-workspace_id", $table, 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        foreach ($this->tables as $table) {
            $key = trim($table, '{}%');
            $this->dropForeignKey("fk-{$key}-workspace_id", $table);
            $this->dropIndex("idx-{$key}-workspace_id", $table);
            $this->dropColumn($table, 'workspace_id');
        }

        return true;
    }
}
