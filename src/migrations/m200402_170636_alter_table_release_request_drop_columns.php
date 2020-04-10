<?php

use \whotrades\rds\migrations\base;

/**
 * Class m200402_170636_alter_table_release_request_drop_columns
 */
class m200402_170636_alter_table_release_request_drop_columns extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('rds.release_request', 'rr_new_post_migrations');
        $this->dropColumn('rds.release_request', 'rr_post_migration_status');
    }

    public function safeDown()
    {
        $this->addColumn('rds.project', 'rr_new_post_migrations', 'text COLLATE "default"');
        $this->addColumn('rds.project', 'rr_post_migration_status', 'varchar COLLATE "default" DEFAULT \'none\'::character varying');
    }
}
