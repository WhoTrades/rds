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


        $nextStatus = $direction == JiraMoveTicket::DIRECTION_UP ? 'Deployed' : 'Rolled back';

        $jira = new JiraApi($this->debugLogger);

        $ticketInfo = $jira->getTicketInfo($ticket);
        $this->debugLogger->message("Processing ticket {$ticket}, status={$ticketInfo['fields']['status']['name']}");

        $transitionId = null;
        foreach ($ticketInfo['transitions'] as $transition) {
            if ($transition['name'] == $nextStatus) {
                $transitionId = $transition['id'];
            }
        }

        if ($transitionId) {
            $this->debugLogger->message("Moving ticket {$ticketInfo['key']} to $nextStatus status");
            $jira->updateTicketTransition($ticketInfo['key'], $transitionId);
        } else {
            $this->debugLogger->message("Invalid ticket {$ticketInfo['key']} status, can't move to $nextStatus");
        }
    }
}