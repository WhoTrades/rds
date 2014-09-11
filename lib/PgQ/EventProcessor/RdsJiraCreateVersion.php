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
        //an: скипаем работу с жирой на всех контурах, кроме прода
        if (!\Config::getInstance()->serviceRds['jira']['tagTickets']) {
            $this->debugLogger->message("Skip processing event as disabled in config");
            return;
        }

        if (!in_array($event->getData()['jira_commit_project'], Yii::app()->params['jiraProjects'])) {
            $this->debugLogger->message("Skip project ".$event->getData()['jira_commit_project']." as not in project list (".json_encode(Yii::app()->params['jiraProjects']).")");
            return;
        }

        $jira = new JiraApi($this->debugLogger);
        $this->debugLogger->message("Creating version {$event->getData()['jira_name']} at project {$event->getData()['jira_project']}");
        $jira->createProjectVersion(
             $event->getData()['jira_project'],
             $event->getData()['jira_name'],
             $event->getData()['jira_description'],
             $event->getData()['jira_archived'] == 'True',
             $event->getData()['jira_released'] == 'True'
        );
    }
}