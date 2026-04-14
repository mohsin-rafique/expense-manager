<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create User Table
 *
 * Creates the main user authentication table with support for:
 * - Username/email authentication
 * - Password hashing
 * - Email verification
 * - Password reset tokens
 * - Login tracking
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

class m260101_183838_user_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%user}}';

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
            'username' => $this->string(255)->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'password_reset_token' => $this->string(255)->unique()->defaultValue(null),
            'email' => $this->string(255)->notNull()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'verification_token' => $this->string(255)->defaultValue(null),
            'last_login_at' => $this->integer()->defaultValue(null),
            'registration_ip' => $this->string(45)->defaultValue(null),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // Indexes for faster lookups
        $this->createIndex(
            'idx-user-status',
            $this->tableName,
            'status'
        );

        $this->createIndex(
            'idx-user-email',
            $this->tableName,
            'email'
        );

        $this->createIndex(
            'idx-user-username',
            $this->tableName,
            'username'
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
