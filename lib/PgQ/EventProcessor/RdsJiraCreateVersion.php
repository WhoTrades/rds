<?php
/**
 * Консьюмер, который разгребает очередь создания версия в jira
 *
 * @author Artem Naumenko
 * @example sphp dev/services/rds/misc/tools/db/pgq/process.php --event-processor=RdsJiraCreateVersion  --queue-name=rds_jira_create_version --consumer-name=rds_jira_create_version_consumer --partition=1  --dsn-name=DSN_DB4  --strategy=simple -vvv process_queue
 */

class PgQ_EventProcessor_RdsJiraCreateVersion extends PgQ\EventProcessor\EventProcessorBase
{
    public function processEvent(PgQ_Event $event)
    {
        
    }
}