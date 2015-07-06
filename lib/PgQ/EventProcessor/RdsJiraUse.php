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

        $this->debugLogger->message("Found tickets between $tagFrom and $tagTo: ".implode(", ", $ticketKeys));

        if (empty($ticketKeys)) {
            return;
        }

        $project = $this->getProjectByBuildTag($tagTo);
        $jira = new JiraApi($this->debugLogger);

        foreach ($ticketKeys as $key) {
            $jiraProjectKey = explode("-", $key)[0];
            if (!in_array($jiraProjectKey, Yii::app()->params['jiraProjects'])) {
                $this->debugLogger->message("Skip ticket $key as unknown project $jiraProjectKey");
                continue;
            }

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

            $assignedProjects = $this->getAssignedProjects($ticket, $jira);

            if ($assignedProjects && !isset($assignedProjects[$project->obj_id])) {
                $this->debugLogger->message("Skip event processing, as ticket $key has no component $project->project_name");
                continue;
            }

            $this->debugLogger->message("Processing ticket {$ticket['key']}, status={$ticket['fields']['status']['name']}");
            $transitionId = null;

            if ($tagFrom < $tagTo && $count = HardMigration::model()->getNotDoneMigrationCountForTicket($ticket['key'])) {
                $jira->addTicketLabel($ticket['key'], "ticket-with-migration");
                $this->debugLogger->message("Found $count not finished hard migration for ticket #{$ticket['key']}, skip this ticket");
                continue;
            }

            if ($tagTo < $tagFrom) {
                $this->scheduleMoveTicket($ticket['key'], JiraMoveTicket::DIRECTION_DOWN, $event);
            } else {
                $commits = JiraCommit::model()->findAllByAttributes([
                    'jira_commit_ticket' => $ticket['key'],
                ]);

                $projectIds = array_keys($assignedProjects);
                $projectIds = array_combine($projectIds, $projectIds);

                foreach ($commits as $commit) {
                    /** @var $commit JiraCommit */
                    $this->debugLogger->info("Processing commit $commit->jira_commit_hash $commit->jira_commit_build_tag");
                    $commitProject = $this->getProjectByBuildTag($commit->jira_commit_build_tag);
                    if ($commitProject->project_name."-".$commitProject->project_current_version < $commit->jira_commit_build_tag) {
                        $this->debugLogger->message("Can't move ticket {$ticket['key']} to next status, as project $commitProject->project_name not released yet (current version: $commitProject->project_current_version < $commit->jira_commit_build_tag)");
                    } else {
                        unset($projectIds[$commitProject->obj_id]);
                    }
                }

                if (count($projectIds) == 0) {
                    $this->scheduleMoveTicket($ticket['key'], JiraMoveTicket::DIRECTION_UP, $event);
                } else {
                    $this->debugLogger->message("Can't move ticket {$ticket['key']} to next status, as not all projects released: ".implode(", ", $projectIds));
                }
            }
        }
    }

    private function scheduleMoveTicket($ticket, $direction, PgQ_Event $event)
    {
        $jiraMove = new JiraMoveTicket();
        $jiraMove->attributes = [
            'jira_ticket' => $ticket,
            'jira_direction' => $direction,
        ];

        $this->debugLogger->message("Adding ticket {$ticket} for moving $direction");
        if (!$jiraMove->save()) {
            $this->debugLogger->error("Can't save JiraMoveTicket, errors: ".json_encode($jiraMove->errors));
            $event->retry(60);
        }
    }

    /** @return Project */
    private function getProjectByBuildTag($tag)
    {
        if (!preg_match('~^([\w-]+)-[\d.]+$~', $tag, $ans)) {
            throw new ApplicationException("Unknown tag $tag");
        }

        $projectName = $ans[1];
        $project = Project::model()->findByAttributes([
            'project_name' => $projectName,
        ]);

        if (!$project) {
            throw new ApplicationException("Unknown project $tag");
        }

        return $project;
    }

    private function getAssignedProjects($ticketInfo)
    {
        $projects = Project::model()->findAll();

        $assignedProjects = [];
        foreach ($ticketInfo['fields']['components'] as $component) {
            foreach ($projects as $val) {
                /** @var $val Project*/
                if ($val->project_name == $component['name']) {
                    $assignedProjects[$val->obj_id] = $val;
                }
            }
        }

        return $assignedProjects;
    }
}