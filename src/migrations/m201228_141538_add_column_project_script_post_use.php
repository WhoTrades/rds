<?php

use yii\db\Migration;
use whotrades\rds\migrations\base;

/**
 * Class m201228_141538_add_column_project_script_post_use
 */
class m201228_141538_add_column_project_script_post_use extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.project', 'script_post_use', 'text');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('rds.project', 'script_post_use');
    }
}
