<?php

use yii\db\Migration;

class m260501_163953_add_fbr_mapping_to_expense_categories extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%expense_categories}}', 'fbr_category', $this->string(100)->null()->after('name'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%expense_categories}}', 'fbr_category');
    }
}
