<?php

use \whotrades\rds\migrations\base;

/**
 * Class m210203_152054_change_index_of_table_rds_migration
 */
class m210203_152054_change_index_of_table_rds_migration extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex('u_migration_name_and_project', 'rds.migration');
        $this->execute('CREATE UNIQUE INDEX u_migration_name_type_and_project ON rds.migration (migration_name, migration_type, migration_project_obj_id)');
    }

    public function safeDown()
    {
        $this->dropIndex('u_migration_name_type_and_project', 'rds.migration');
        $this->execute('CREATE UNIQUE INDEX u_migration_name_and_project ON rds.migration (migration_name, migration_project_obj_id)');
    }
}
