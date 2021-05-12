<?php

use \whotrades\rds\migrations\base;

/**
 * Class m210512_155143_alter_table_rds_migration_add_modified_update_trigger
 */
class m210512_155143_alter_table_rds_migration_add_modified_update_trigger extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            CREATE TRIGGER t_rds_migration_modified
            BEFORE UPDATE
            ON rds.migration FOR EACH ROW
            EXECUTE PROCEDURE public.t_obj_modified();
        ');
    }

    public function safeDown()
    {
        $this->execute("DROP TRIGGER t_rds_migration_modified ON rds.migration");
    }
}
