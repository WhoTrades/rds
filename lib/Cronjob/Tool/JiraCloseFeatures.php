<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=JiraCloseFeatures -vv
 */
class Cronjob_Tool_JiraCloseFeatures extends RdsSystem\Cron\RabbitDaemon
{
    //an: Интервал, с которым мы пытается заново смержить задачу. Например, если разработчик разрулит конфликты - мы сами
    //это заметим и передвинем задачу в следующий статус

    const MERGE_INTERVAL = 600;
    const LABEL_MERGING = "merging";

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $this->debugLogger->message("Start working");
        $jira = new JiraApi($this->debugLogger);
        $c = new CDbCriteria();
        $c->compare("jf_status", '<>'.JiraFeature::STATUS_CLOSED);
        $nonClosedFeatures = JiraFeature::model()->findAll($c);
        $tickets = [];
        foreach ($nonClosedFeatures as $feature) {
            /** @var $feature JiraFeature */
            $tickets[] = $feature->jf_ticket;
        }
        $tickets = array_unique($tickets);

        $this->debugLogger->message("Processing tickets: ".implode(",", $tickets));

        foreach ($tickets as $ticket) {
            $this->debugLogger->debug("Processing ticket $ticket");
            $ticketInfo = $jira->getTicketInfo($ticket);
            $this->debugLogger->debug("Ticket $ticket status is {$ticketInfo['fields']['status']['name']}");
            if ($ticketInfo['fields']['status']['name'] == \Jira\Status::STATUS_CLOSED) {
                $this->debugLogger->message("Closing features with ticket=$ticket, as ticket was closed");
                JiraFeature::model()->updateAll(['jf_status' => JiraFeature::STATUS_CLOSED], 'jf_ticket=:ticket', [':ticket' => $ticket]);
            }
        }
        $this->debugLogger->message("Finish working");
    }
}
