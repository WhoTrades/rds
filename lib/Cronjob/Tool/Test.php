<?php
use \Cronjob\ConfigGenerator;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */
class Cronjob_Tool_Test extends Cronjob\Tool\ToolBase
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array();
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model = new JiraCreateVersion();
        $model->jira_version = 'comon-62.00.012.156';
        $model->jira_name = 'test';
        $model->jira_project = 'WTA';
        $model->jira_archived = false;
        $model->jira_released = false;

        var_export($model->save());
    }
}
