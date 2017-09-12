<?php

use yii\db\Migration;

class m170908_130001_project_scripts_cron_deploy extends Migration
{
    public function up()
    {
        $this->addColumn('rds.project', 'script_cron', 'text');
        $this->addColumn('rds.project', 'script_deploy', 'text');
    }

    public function down()
    {
        $this->dropColumn('rds.project', 'script_cron');
        $this->dropColumn('rds.project', 'script_deploy');
    }
}
