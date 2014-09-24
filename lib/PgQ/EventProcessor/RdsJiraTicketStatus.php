<?php
/**
 * Консьюмер, который разгребает очередь тегирования тикетов в jira нашими сборками в RDS
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsJiraTicketStatus  --queue-name=rds_jira_commit --consumer-name=rds_jira_ticket_status_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsJiraTicketStatus extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        //an: скипаем работу с жирой на всех контурах, кроме прода
        if (!\Config::getInstance()->serviceRds['jira']['checkTicketStatus']) {
            $this->debugLogger->message("Skip processing event as disabled in config");
            return;
        }

        if (!in_array($event->getData()['jira_commit_project'], Yii::app()->params['jiraProjects'])) {
            $this->debugLogger->message("Skip project ".$event->getData()['jira_commit_project']." as not in project list (".json_encode(Yii::app()->params['jiraProjects']).")");
            return;
        }

        $jiraApi = new JiraApi($this->debugLogger);

        $ticket = $event->getData()['jira_commit_ticket'];

        try {
            $info = $jiraApi->getTicketInfo($ticket);
        } catch (ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            if ($e->getHttpCode() != 404) {
                throw $e;
            }
        }
        
        if (!in_array($info['fields']['status']['name'], ['Готово к выкладке', 'Закрыт', 'Готово к приемке'])) {
            $this->debugLogger->message("Marking ticket $ticket as invalid status");
            $jiraApi->addTicketLabel($ticket, 'deployed-at-invalid-status');
        }
    }
}