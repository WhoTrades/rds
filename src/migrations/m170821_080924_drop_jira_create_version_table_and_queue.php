<?php

use yii\db\Migration;

class m170821_080924_drop_jira_create_version_table_and_queue extends Migration
{
    public function safeUp()
    {
        $this->execute("SELECT pgq.unregister_consumer('rds_jira_create_version_1', 'rds_jira_create_version_consumer')");
        $this->execute("SELECT pgq.drop_queue('rds_jira_create_version_1')");
        $this->dropTable('rds.jira_create_version');
    }
}
