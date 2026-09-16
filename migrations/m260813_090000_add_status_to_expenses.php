<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

use yii\db\Migration;

/**
 * Migration: Add status to Expenses
 *
 * Workflow marker for an expense record. New expenses are "active"; a record
 * created through the Duplicate action starts as "draft" so it can be reviewed
 * and edited before being marked active. Existing rows are backfilled to
 * "active" by the column default.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class m260813_090000_add_status_to_expenses extends Migration
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
        $this->addColumn(
            $this->tableName,
            'status',
            $this->string(20)->notNull()->defaultValue('active')->after('reference')
        );

        $this->createIndex('idx-expenses-status', $this->tableName, 'status');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): bool
    {
        $this->dropIndex('idx-expenses-status', $this->tableName);
        $this->dropColumn($this->tableName, 'status');

        return true;
    }
}
