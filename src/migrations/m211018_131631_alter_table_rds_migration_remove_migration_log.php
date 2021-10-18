<?php

use \whotrades\rds\migrations\base;

/**
 * Class m211018_131631_alter_table_rds_migration_remove_migration_log
 */
class m211018_131631_alter_table_rds_migration_remove_migration_log extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('rds.migration', 'migration_log');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('rds.migration', 'migration_log', 'text');
    }
}
