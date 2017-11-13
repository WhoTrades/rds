<?php

use yii\db\Migration;

class m171031_114929_hstore_to_json extends Migration
{
    public function safeUp()
    {
        $this->execute('ALTER TABLE rds.project ALTER COLUMN project_build_subversion TYPE text;');
        $this->execute("UPDATE rds.project SET project_build_subversion=hstore_to_json(project_build_subversion::hstore)::varchar");
    }

    public function down()
    {
        return false;
    }
}
