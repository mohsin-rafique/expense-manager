<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Workspaces & Workspace Members
 *
 * Introduces the multi-user / team foundation. A workspace is a shared
 * container that owns transactional data; users join workspaces through
 * workspace_members, each with a role (owner/admin/member/viewer).
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260608_120000_create_workspaces extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // ── workspaces ──────────────────────────────────────────────
        $this->createTable('{{%workspaces}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(191)->notNull(),
            'owner_id' => $this->integer()->notNull(),
            'is_personal' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        $this->createIndex('idx-workspaces-owner_id', '{{%workspaces}}', 'owner_id');
        $this->addForeignKey('fk-workspaces-owner_id', '{{%workspaces}}', 'owner_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        // ── workspace_members ───────────────────────────────────────
        $this->createTable('{{%workspace_members}}', [
            'id' => $this->primaryKey(),
            'workspace_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->defaultValue(null), // null while an email invite is pending
            'role' => $this->string(20)->notNull()->defaultValue('member'), // owner|admin|member|viewer
            'status' => $this->string(20)->notNull()->defaultValue('active'), // active|pending
            'invited_email' => $this->string(191)->defaultValue(null),
            'invite_token' => $this->string(64)->defaultValue(null),
            'created_at' => $this->integer()->defaultValue(null),
            'updated_at' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        $this->createIndex('idx-wm-workspace_id', '{{%workspace_members}}', 'workspace_id');
        $this->createIndex('idx-wm-user_id', '{{%workspace_members}}', 'user_id');
        $this->createIndex('idx-wm-invite_token', '{{%workspace_members}}', 'invite_token');
        // A user can appear at most once per workspace
        $this->createIndex('idx-wm-unique', '{{%workspace_members}}', ['workspace_id', 'user_id'], true);

        $this->addForeignKey('fk-wm-workspace_id', '{{%workspace_members}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-wm-user_id', '{{%workspace_members}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropForeignKey('fk-wm-user_id', '{{%workspace_members}}');
        $this->dropForeignKey('fk-wm-workspace_id', '{{%workspace_members}}');
        $this->dropTable('{{%workspace_members}}');

        $this->dropForeignKey('fk-workspaces-owner_id', '{{%workspaces}}');
        $this->dropTable('{{%workspaces}}');

        return true;
    }
}
