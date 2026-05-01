<?php

use yii\db\Migration;

class m260501_163407_add_country_to_profile extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 'char(2)' is standard for ISO codes (e.g., 'PK', 'US')
        $this->addColumn('{{%profile}}', 'country_code', $this->char(2)->defaultValue('PK')->after('location'));
        $this->createIndex('idx-profile-country_code', '{{%profile}}', 'country_code');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop index FIRST, then the column
        $this->dropIndex('idx-profile-country_code', '{{%profile}}');
        $this->dropColumn('{{%profile}}', 'country_code');
    }
}
