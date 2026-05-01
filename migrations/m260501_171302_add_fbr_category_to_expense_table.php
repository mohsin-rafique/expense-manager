<?php

use yii\db\Migration;

class m260501_171302_add_fbr_category_to_expense_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%expenses}}', 'fbr_category', $this->string(100)->after('expense_category_id'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%expenses}}', 'fbr_category');
    }
}
