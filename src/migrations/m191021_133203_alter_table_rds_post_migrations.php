<?php

use \whotrades\rds\migrations\base;

/**
 * Class m191021_133203_alter_table_rds_post_migrations
 */
class m191021_133203_alter_table_rds_post_migrations extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('ALTER TABLE rds.post_migration
                            ADD CONSTRAINT rds_post_migration_project_obj_id
                            FOREIGN KEY (pm_project_obj_id)
                            REFERENCES rds.project (obj_id) MATCH SIMPLE
                            ON UPDATE RESTRICT
                            ON DELETE CASCADE;');
    }

    public function safeDown()
    {
        $this->execute('ALTER TABLE rds.post_migration DROP CONSTRAINT rds_post_migration_project_obj_id;');
    }
}
