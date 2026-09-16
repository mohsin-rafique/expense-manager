<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Add bank_id to Expenses
 *
 * Optional link from an expense to the bank it went through (for Card / Bank
 * Transfer payments). Nullable; cleared to NULL if the bank is deleted.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class m260717_130100_add_bank_id_to_expenses extends Migration
{
    /**
     * @var string The table name
     */
    private string $tableName = '{{%expenses}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp(): bool
    {
        $this->addColumn($this->tableName, 'bank_id', $this->integer()->null()->after('payment_method'));

        $this->createIndex('idx-expenses-bank_id', $this->tableName, 'bank_id');

        $this->addForeignKey(
            'fk-expenses-bank_id',
            $this->tableName,
            'bank_id',
            '{{%banks}}',
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
        $this->dropForeignKey('fk-expenses-bank_id', $this->tableName);
        $this->dropIndex('idx-expenses-bank_id', $this->tableName);
        $this->dropColumn($this->tableName, 'bank_id');

        return true;
    }
}
