<?php

use yii\db\Migration;

class m170829_115309_add_column_project_script_migration_remove extends Migration
{
    public function up()
    {
        $this->addColumn('rds.project', 'script_remove_release', 'text');
    }

    public function down()
    {
        $this->dropColumn('rds.project', 'script_remove_release');
    }
}
