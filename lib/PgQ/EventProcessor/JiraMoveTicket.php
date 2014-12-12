<?php
/**
 * Консьюмер, который двигает статусы тикетов из Ready for deploy -> Ready for acceptance в случае выкатывания релиза, и обратно в случае откатывания
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=JiraMoveTicket  --queue-name=rds_jira_move_ticket --consumer-name=rds_jira_move_ticket_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_JiraMoveTicket extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        $ticket = $event->getData()['jira_ticket'];
        $direction = $event->getData()['jira_direction'];

        $transition = $direction == JiraMoveTicket::DIRECTION_UP ? Jira\Transition::DEPLOYED : Jira\Transition::ROLL_BACK;

        $jira = new JiraApi($this->debugLogger);

        $ticketInfo = $jira->getTicketInfo($ticket);
        $this->debugLogger->message("Processing ticket {$ticket}, status={$ticketInfo['fields']['status']['name']}");


        if (!in_array($ticketInfo["fields"]["status"]["name"], [\Jira\Status::STATUS_READY_FOR_DEPLOYMENT, \Jira\Status::STATUS_READY_FOR_ACCEPTANCE])) {
            $this->debugLogger->message("Skip transition ticket $ticket, as it is not in valid status");
            return;
        }

        $jira->transitionTicket($ticketInfo, $transition);
    }
}