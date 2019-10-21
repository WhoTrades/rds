<?php

use \whotrades\rds\migrations\base;

/**
 * Class m191021_133202_alter_table_rds_hard_migrations
 */
class m191021_133202_alter_table_rds_hard_migrations extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('ALTER TABLE rds.hard_migration DROP CONSTRAINT rds_migration_project_obj_id;');
        $this->execute('ALTER TABLE rds.hard_migration
                            ADD CONSTRAINT rds_hard_migration_project_obj_id
                            FOREIGN KEY (migration_project_obj_id)
                            REFERENCES rds.project (obj_id) MATCH SIMPLE
                            ON UPDATE RESTRICT
                            ON DELETE CASCADE;');
    }

    public function safeDown()
    {
        $this->execute('ALTER TABLE rds.hard_migration DROP CONSTRAINT rds_hard_migration_project_obj_id;');
        $this->execute('ALTER TABLE rds.hard_migration
                            ADD CONSTRAINT rds_migration_project_obj_id
                            FOREIGN KEY (migration_project_obj_id)
                            REFERENCES rds.project (obj_id) MATCH SIMPLE
                            ON UPDATE RESTRICT
                            ON DELETE RESTRICT;');
    }
}
