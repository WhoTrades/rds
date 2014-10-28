<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */
class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $ticket = "WTA-69";
        $jira = new JiraApi($this->debugLogger);
        $text = uniqid();
        $this->debugLogger->message("Adding text $text");
        $lastComment = end($jira->getTicketInfo($ticket)['fields']['comment']['comments']);
        if ($lastComment['author']['name'] == $jira->getUserName()) {
            $this->debugLogger->message("Updating last comment {$lastComment['self']}");
            $jira->updateComment($ticket, $lastComment['id'], $lastComment['body']."\n".date("d.m.Y H:i:s").": ".$text);
        } else {
            $this->debugLogger->message("Adding new comment");
            $jira->addComment($ticket, $text);
        }
    }
}
