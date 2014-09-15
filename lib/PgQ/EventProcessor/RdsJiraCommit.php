<?php
/**
 * Консьюмер, который разгребает очередь тегирования тикетов в jira нашими сборками в RDS
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

        if (!in_array($event->getData()['jira_commit_project'], Yii::app()->params['jiraProjects'])) {
            $this->debugLogger->message("Skip project ".$event->getData()['jira_commit_project']." as not in project list (".json_encode(Yii::app()->params['jiraProjects']).")");
            return;
        }

        $jiraApi = new JiraApi($this->debugLogger);

        $ticket = $event->getData()['jira_commit_ticket'];
        $fixVersion = $event->getData()['jira_commit_build_tag'];

        list($jiraProject) = explode('-', $ticket);

        if (!preg_match("~^(.*)-(.*?)$~", $fixVersion, $ans)) {
            throw new Exception("Invalid fixVersion $fixVersion");
        }

        list(,$project, $version) = $ans;

        $this->debugLogger->message("Detected project=$project, version=$version");

        $Project = Project::model()->findByAttributes(['project_name' => $project]);
        $releaseRequest = \ReleaseRequest::model()->findByAttributes([
            'rr_project_obj_id' => $Project->obj_id,
            'rr_build_version' => $version,
        ]);


        if (!$releaseRequest) {
            $this->debugLogger->message("Skip creating tag of version $fixVersion because release request was deleted");
            return;
        }


        $build = $releaseRequest->builds[0];
        $this->debugLogger->message("Creating version $fixVersion at project $jiraProject");
        try {
            $jiraApi->createProjectVersion(
                 $jiraProject,
                 $fixVersion,
                 'Сборка #'.$build->build_release_request_obj_id.', '.$releaseRequest->rr_user.' [auto]',
                 true,
                 false
            );
        } catch (ServiceBase\HttpRequest\Exception\ResponseCode $e) {

        }

        $this->debugLogger->message("Adding fixVersion $fixVersion to ticket $ticket");
        $jiraApi->addTicketFixVersion($ticket, $fixVersion);

        try {
            $info = $jiraApi->getTicketInfo($ticket);
        } catch (ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            if ($e->getCode() != 404) {
                throw $e;
            }
        }

        if (!empty($info['fields']['parent']['key'])) {
            $parent = $info['fields']['parent']['key'];
            $this->debugLogger->message("Ticket $ticket has parent $parent, adding fixVersion to parent too");
            $jiraApi->addTicketFixVersion($parent, $fixVersion);
        }
    }
}