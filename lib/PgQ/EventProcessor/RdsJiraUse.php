<?php
/**
 * Консьюмер, который двигает статусы тикетов из Ready for deploy -> Ready for acceptance в случае выкатывания релиза, и обратно в случае откатывания
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsJiraUse  --queue-name=rds_jira_use --consumer-name=rds_jira_use_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsJiraUse extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        //an: скипаем работу с жирой на всех контурах, кроме прода
        if (!\Config::getInstance()->serviceRds['jira']['transitionTickets']) {
            $this->debugLogger->message("Skip processing event as disabled in config");
            return;
        }

        $tagFrom = $event->getData()['jira_use_from_build_tag'];
        $tagTo = $event->getData()['jira_use_to_build_tag'];

        $sql = "SELECT DISTINCT jira_commit_ticket FROM rds.jira_commit WHERE jira_commit_build_tag > :tagFrom AND jira_commit_build_tag <= :tagTo";
        $ticketKeys = Yii::app()->db->createCommand($sql)->queryColumn([
            ':tagFrom' => min($tagFrom, $tagTo),
            ':tagTo' => max($tagFrom, $tagTo),
        ]);

        $this->debugLogger->message("Found tickets between $tagFrom and $tagTo: ".implode(", $ticketKeys"));

        if (empty($ticketKeys)) {
            return;
        }

        $jira = new JiraApi($this->debugLogger);

        foreach ($ticketKeys as $key) {
            try {
                $ticket = $jira->getTicketInfo($key);
            } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
                if ($e->getHttpCode() == 404 && $e->getResponse() == '{"errorMessages":["Issue Does Not Exist"],"errors":{}}') {
                    $this->debugLogger->message("Can't move ticket $key, as ticket was deleted");
                    continue;
                } else {
                    throw $e;
                }
            }

            $this->debugLogger->message("Processing ticket {$ticket['key']}, status={$ticket['fields']['status']['name']}");
            $transitionId = null;

            if ($tagFrom < $tagTo && $count = HardMigration::model()->getNotDoneMigrationCountForTicket($ticket['key'])) {
                $jira->addTicketLabel($ticket['key'], "ticket-with-migration");
                $this->debugLogger->message("Found $count not finished hard migration for ticket #{$ticket['key']}, skip this ticket");
            }

            $direction = $tagFrom < $tagTo ? JiraMoveTicket::DIRECTION_UP : JiraMoveTicket::DIRECTION_DOWN;
            $jiraMove = new JiraMoveTicket();
            $jiraMove->attributes = [
                'jira_ticket' => $ticket['key'],
                'jira_direction' => $direction,
            ];

            $this->debugLogger->message("Adding ticket {$ticket['key']} for moving $direction");

            if (!$jiraMove->save()) {
                $this->debugLogger->error("Can't save JiraMoveTicket, errors: ".json_encode($jiraMove->errors));
                $event->retry(60);
            }
        }
    }
}