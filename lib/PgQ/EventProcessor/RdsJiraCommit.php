<?php
/**
 * Консьюмер, который разгребает очередь создания версия в jira
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsJiraCommit  --queue-name=rds_jira_commit --consumer-name=rds_jira_commit_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsJiraCommit extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        //an: скипаем работу с жирой на всех контурах, кроме прода
        if (!\Config::getInstance()->serviceRds['jira']['tagTickets']) {
            $this->debugLogger->message("Skip processing event as disabled in config");
            return;
        }

        if (!preg_match('~^WT~', $event->getData()['jira_commit_project'])) {
            $this->debugLogger->message("Skip project ".$event->getData()['jira_commit_project']." as not WT* project");
            return;
        }
        $jiraApi = new JiraApi($this->debugLogger);

        $ticket = $event->getData()['jira_commit_ticket'];
        $fixVersion = $event->getData()['jira_commit_build_tag'];
        $this->debugLogger->message("Adding fixVersion $fixVersion to ticket $ticket");
        $jiraApi->addTicketFixVersion($ticket, $fixVersion);
    }
}