<?php

use \whotrades\rds\migrations\base;

/**
 * Class m221208_160326_alter_table_rds_project_add_up_hard
 */
class m221208_160326_alter_table_rds_project_add_up_hard extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.project', 'script_migration_up_hard', 'text');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('rds.project', 'script_migration_up_hard');
    }
}
