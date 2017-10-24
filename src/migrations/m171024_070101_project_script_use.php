<?php

use yii\db\Migration;

class m171024_070101_project_script_use extends Migration
{
    public function up()
    {
        $this->addColumn('rds.project', 'script_use', 'text');
    }

    public function down()
    {
        $this->dropColumn('rds.project', 'script_use');

        return false;
    }
}
