<?php

use yii\db\Migration;
use whotrades\rds\migrations\base;
use yii\db\Schema;

/**
 * Class m210118_091729_add_column_profile_locale
 */
class m210118_091729_add_column_profile_locale extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.{{%profile}}', 'locale', Schema::TYPE_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('rds.{{%profile}}', 'locale');
    }
}
