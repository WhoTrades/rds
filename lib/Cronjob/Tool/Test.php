<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;
use RdsSystem\lib\CommandExecutor;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $jira = new JiraApi($this->debugLogger);
        $info = $jira->getTicketInfo('WTTES-6');
        $lastDeveloper = $jira->getLastDeveloper($info);

        $this->debugLogger->message("Last developer: ".$lastDeveloper);
    }
}

