<?php

/**
 * Class m180927_073202_alter_table_rds_project_config_history
 */
class m180927_073202_alter_table_rds_project_config_history extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.project_config_history', 'pch_log', 'text');
    }

    public function safeDown()
    {
        $this->dropColumn('rds.project_config_history', 'pch_log');
    }
}
