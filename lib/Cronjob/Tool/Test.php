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
        $jiraApi = new JiraApi($this->debugLogger);

        $jiraApi->addCommend('WTA-26', 'Каммент!');


        return;
        $jira->updateTicketTransition('WTI-422', 211);
        $jira->addTicketFixVersion('WTI-422', '62.x');
        $jira->addTicketFixVersion('WTI-422', 'comon-62.00.032.367');
    }
}
