<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;
use RdsSystem\lib\CommandExecutor;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        /** @var $event PgQ_Event*/

        $pullRequestTitle = 'Рецензия кода по задаче ...';
        $pullRequestDescription = 'Рецензия создана автоматически, если она не нужна - просто закройте её';
        $pullRequestProject = 'WT';
        $pullRequestRepo = 'sparta';

        $fromBranch = 'fg98fdgyhdfjk1';
        $toBranch = 'master';
        $jiraProject = 'WTA';

        $httpSender = new \ServiceBase\HttpRequest\RequestSender($this->debugLogger);
        $branches = json_decode($httpSender->getRequest("http://git.whotrades.net/branches.json", [], 30), true);
        if (!$branches) {
            throw new ApplicationException("Invalid json received from http://git.whotrades.net/branches.json");
        }
        if (empty($branches[$pullRequestRepo])) {
            throw new ApplicationException("No $pullRequestRepo repository fount at http://git.whotrades.net/branches.json");
        }

        if (empty($branches[$pullRequestRepo][$fromBranch])) {
            $this->debugLogger->message("Branch $fromBranch not found at http://git.whotrades.net/branches.json as repository $pullRequestRepo, so skip event");
            return;
        }

        $jira = new JiraApi($this->debugLogger);

        $stash = new \CompanyInfrastructure\StashApi($this->debugLogger);

        $roles = $jira->getProjectInfo($jiraProject)['roles'];

        if (empty(Yii::app()->params['stashPullRequestConfig'][$jiraProject])) {
            $this->debugLogger->message("No roles for project $jiraProject assigned, skip event");
            return;
        }

        $assignee = [];
        foreach (Yii::app()->params['stashPullRequestConfig'][$jiraProject]['roles'] as $role) {
            $this->debugLogger->message($roles[$role]);
            foreach ($jira->getProjectRolePeople($roles[$role]) as $people) {
                foreach($people as $person) {
                    $assignee[] = $person['name'];
                }
            }
        }

        try {
            $stash->createPullRequest($pullRequestTitle, $pullRequestDescription, $pullRequestProject, $pullRequestRepo, $fromBranch, $toBranch, $assignee);
        } catch (\ServiceBase\HttpRequest\Exception\ResponseCode $e) {
            $this->debugLogger->error("Http code received: ".$e->getHttpCode());
            $json = json_decode($e->getResponse(), true);
            if (json_last_error()) {
                $this->debugLogger->dump()->message('an', 'invalid_json_received_from_stash', false, [
                    'eventData' => $event->getData(),
                    'assignee' => $assignee,
                    'httpCode' => $e->getHttpCode(),
                    'httpResponse' => $e->getResponse(),
                ])->critical()->save();

                $event->retry(60);
                return;
            }
            foreach ($json['errors'] as $error) {
                switch ($error['exceptionName']) {
                    case 'com.atlassian.stash.pull.DuplicatePullRequestException':
                        //an: Такой pull request уже есть, игнорируем событие. @see https://developer.atlassian.com/static/javadoc/stash.old-perms-pre-feb4/1.3.0/api/reference/com/atlassian/stash/pull/DuplicatePullRequestException.html
                        $url = $error['existingPullRequest']['links']['self'][0]['href'];
                        $this->debugLogger->message("Pull request for branch $fromBranch already exists, skip it. URL: $url");
                        return;
                        break;
                    default:
                        $this->debugLogger->dump()->message('an', 'unknown_error_during_stash_request', false, [
                            'eventData' => $event->getData(),
                            'assignee' => $assignee,
                            'httpCode' => $e->getHttpCode(),
                            'response' => $json,
                        ])->critical()->save();

                        $event->retry(60);
                        return;
                }
            }
        }
    }
}
