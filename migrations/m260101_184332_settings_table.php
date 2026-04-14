<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Create Settings Table
 *
 * Creates the user settings table for application preferences:
 * - Company/business information
 * - Regional settings (timezone, date/time formats)
 * - Currency configuration
 * - Branding (logo, favicon)
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260101_184332_settings_table extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%settings}}';

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

            // Company Information
            'company_name' => $this->string(191)->notNull(),
            'site_title' => $this->string(191)->defaultValue(null),
            'phone' => $this->string(191)->defaultValue(null),
            'email' => $this->string(191)->defaultValue(null),

            // Regional Settings
            'timezone' => $this->string(191)->defaultValue(null),
            'date_format' => $this->string(191)->defaultValue(null),
            'time_format' => $this->string(191)->defaultValue(null),

            // Currency Settings
            'currency' => $this->string(5)->defaultValue(null),
            'currency_position' => $this->string(16)->defaultValue(null),
            'thousand_separator' => $this->string(1)->defaultValue(null),
            'decimal_separator' => $this->string(1)->defaultValue(null),
            'decimal_places' => $this->integer()->defaultValue(null),

            // Branding
            'logo' => $this->string(191)->defaultValue(null),
            'favicon' => $this->string(191)->defaultValue(null),
        ], $tableOptions);

        // Index for faster lookups by user
        $this->createIndex(
            'idx-settings-user_id',
            $this->tableName,
            'user_id'
        );

        // Foreign key to user table
        $this->addForeignKey(
            'fk-settings-user_id',
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
        $this->dropForeignKey('fk-settings-user_id', $this->tableName);
        $this->dropTable($this->tableName);

        return true;
    }
}
