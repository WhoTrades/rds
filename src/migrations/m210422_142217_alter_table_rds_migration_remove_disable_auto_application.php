<?php

use \whotrades\rds\migrations\base;

/**
 * Class m210422_142217_alter_table_rds_migration_remove_disable_auto_application
 */
class m210422_142217_alter_table_rds_migration_remove_disable_auto_application extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('rds.migration', 'migration_auto_apply');
    }

    public function safeDown()
    {
        $this->addColumn('rds.migration', 'migration_auto_apply', 'BOOLEAN DEFAULT TRUE');
    }
}
