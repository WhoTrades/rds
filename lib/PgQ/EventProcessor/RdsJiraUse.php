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

        $jira = new JiraApi($this->debugLogger);

        $projects = Yii::app()->params['jiraProjects'];
        $allVersions = [];
        foreach ($projects as $project) {
            $allVersions = array_merge($allVersions, $jira->getAllVersions($project));
        }

        $versions = array_filter($allVersions, function($version) use ($tagFrom, $tagTo){
            return $version['name'] > min($tagFrom, $tagTo) && $version['name'] <= max($tagFrom, $tagTo);
        });

        $names = array_map(function($version){ return $version['name'];}, $versions);
        $this->debugLogger->message("Found between $tagFrom and $tagTo: ".implode(", $names"));

        if (empty($versions)) {
            return;
        }

        $tickets = $jira->getTicketsByVersions($names);


        foreach ($tickets['issues'] as $ticket) {
            $this->debugLogger->message("Processing ticket {$ticket['key']}, status={$ticket['fields']['status']['name']}");
            $transitionId = null;

            if ($count = HardMigration::model()->getNotDoneMigrationCountForTicket($ticket)) {
                $this->debugLogger->message("Found $count not finished hard migration for ticket #$ticket, retry it in 60 seconds");
                $event->retry(60);
                continue;
            }

            $nextStatus = $tagFrom < $tagTo ? 'Deployed' : 'Rolled back';
            foreach ($ticket['transitions'] as $transition) {
                if ($transition['name'] == $nextStatus) {
                    $transitionId = $transition['id'];
                }
            }
            if ($transitionId) {
                $this->debugLogger->message("Moving ticket {$ticket['key']} to $nextStatus status");
                $jira->updateTicketTransition($ticket['key'], $transitionId);
            } else {
                $this->debugLogger->message("Invalid ticket {$ticket['key']} status, can't move to $nextStatus");
            }
        }
    }
}