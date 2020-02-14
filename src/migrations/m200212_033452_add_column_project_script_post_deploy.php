<?php

use \whotrades\rds\migrations\base;

/**
 * Class m200212_033452_add_column_project_script_post_deploy
 */
class m200212_033452_add_column_project_script_post_deploy extends base
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('rds.project', 'script_post_deploy', 'text');
    }

    public function safeDown()
    {
        $this->dropColumn('rds.project', 'script_post_deploy');
    }
}
