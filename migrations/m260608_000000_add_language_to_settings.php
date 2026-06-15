<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Add Language Preference to Settings Table
 *
 * Adds the `language` column used by the multi-language (i18n) feature to
 * store each user's preferred UI language code (e.g. 'en', 'es', 'fr',
 * 'ur', 'de'). Defaults to English so existing rows remain valid.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class m260608_000000_add_language_to_settings extends Migration
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
        $this->addColumn(
            $this->tableName,
            'language',
            $this->string(10)->defaultValue('en')->after('decimal_places')
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropColumn($this->tableName, 'language');

        return true;
    }
}
