<?php
use yii\db\Migration;

class m170921_143132_project_script_build extends Migration
{
    public function up()
    {
        $this->addColumn('rds.project', 'script_build', 'text');
    }

    public function down()
    {
        $this->dropColumn('rds.project', 'script_build');
    }
}
