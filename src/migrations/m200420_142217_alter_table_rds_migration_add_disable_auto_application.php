<?php

use \whotrades\rds\migrations\base;

/**
 * Class m200420_142217_alter_table_rds_migration_add_disable_auto_application
 */
class m200420_142217_alter_table_rds_migration_add_disable_auto_application extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.migration', 'migration_auto_apply', 'BOOLEAN DEFAULT TRUE');
    }

    public function safeDown()
    {
        $this->dropColumn('rds.migration', 'migration_auto_apply');
    }
}
