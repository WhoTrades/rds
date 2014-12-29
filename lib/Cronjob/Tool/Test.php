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
        $ticket = 'WTTES-20';
        $ticketInfo = $jira->getTicketInfo($ticket);
        $lastDeveloper = $jira->getLastDeveloperNotRds($ticketInfo);
        $this->debugLogger->message($lastDeveloper);
    }
}

