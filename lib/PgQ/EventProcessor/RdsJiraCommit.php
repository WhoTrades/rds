<?php
/**
 * Консьюмер, который разгребает очередь создания версия в jira
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsJiraCommit  --queue-name=rds_jira_commit --consumer-name=rds_jira_commit_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple  -v process_queue
 */

class PgQ_EventProcessor_RdsJiraCommit extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        if ($event->getData()['jira_commit_project'] != 'WTA') {
            $this->debugLogger->message("Skip project ".$event->getData()['jira_commit_project']);
            return;
        }
        $jiraApi = new JiraApi($this->debugLogger);

        $ticket = $event->getData()['jira_commit_ticket'];
        $fixVersion = $event->getData()['jira_commit_build_tag'];
        $this->debugLogger->message("Adding fixVersion $fixVersion to ticket $ticket");
        $jiraApi->addTicketFixVersion($ticket, $fixVersion);
    }
}