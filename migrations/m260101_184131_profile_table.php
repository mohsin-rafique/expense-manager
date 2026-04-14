<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Profile Table
 *
 * Creates the user profile table for extended user information:
 * - Personal details (name, phone, location)
 * - Professional info (designation, website)
 * - Avatar and banner images (custom uploads)
 * - Gravatar integration (fallback for avatar)
 * - Timezone preferences
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260101_184131_profile_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%profile}}';

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
            'user_id' => $this->integer()->notNull(),

            // Personal Information
            'name' => $this->string(191)->defaultValue(null),
            'designation' => $this->string(191)->defaultValue(null),
            'phone' => $this->string(191)->defaultValue(null),
            'location' => $this->string(191)->defaultValue(null),
            'website' => $this->string(191)->defaultValue(null),
            'timezone' => $this->string(191)->defaultValue(null),
            'bio' => $this->text()->defaultValue(null),

            // Avatar & Banner Images
            'avatar' => $this->string(191)->defaultValue(null)->comment('Custom avatar filename'),
            'banner' => $this->string(191)->defaultValue(null)->comment('Custom banner filename'),

            // Gravatar Integration (fallback)
            'gravatar_email' => $this->string(191)->defaultValue(null),
            'gravatar_id' => $this->string(32)->defaultValue(null),
        ], $tableOptions);

        // Primary key on user_id (one-to-one relationship)
        $this->addPrimaryKey(
            'pk-profile-user_id',
            $this->tableName,
            'user_id'
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-profile-user_id',
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
        $this->dropForeignKey('fk-profile-user_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
