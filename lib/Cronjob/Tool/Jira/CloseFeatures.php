<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Jira_CloseFeatures -vv
 */
class Cronjob_Tool_Jira_CloseFeatures extends RdsSystem\Cron\RabbitDaemon
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
        $c->compare("jf_status", '<>'.JiraFeature::STATUS_REMOVED);
        $c->compare("jf_status", '<>'.JiraFeature::STATUS_REMOVING);
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
            try {
                $ticketInfo = $jira->getTicketInfo($ticket);
                $this->debugLogger->debug("Ticket $ticket status is {$ticketInfo['fields']['status']['name']}");
                if ($ticketInfo['fields']['status']['name'] == \Jira\Status::STATUS_CLOSED) {
                    $this->debugLogger->message("Closing features with ticket=$ticket, as ticket was closed");
                    JiraFeature::model()->updateAll(['jf_status' => JiraFeature::STATUS_CLOSED], 'jf_ticket=:ticket', [':ticket' => $ticket]);
                }
            } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
                if ($e->getHttpCode() == 404 && $e->getResponse() == '{"errorMessages":["Issue Does Not Exist"],"errors":{}}') {
                    $this->debugLogger->message("Closing features with ticket=$ticket, as ticket was deleted");
                    JiraFeature::model()->updateAll(['jf_status' => JiraFeature::STATUS_CLOSED], 'jf_ticket=:ticket', [':ticket' => $ticket]);
                } else {
                    throw $e;
                }
            }
        }
        $this->debugLogger->message("Finish working");
    }
}
