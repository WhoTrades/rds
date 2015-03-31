<?php
use \Cronjob\ConfigGenerator;

/**
 * @example
 * sphp dev/services/rds/misc/tools/runner.php --tool=JiraFixVersionsRelease -vv
 */
class Cronjob_Tool_JiraFixVersionsRelease extends Cronjob\Tool\ToolBase
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [
            'dry-run' => [
                'desc' => 'Do noting, only show information',
            ],
        ];
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $dryRun = $cronJob->getOption('dry-run');
        $projects = \Yii::app()->params['jiraProjects'];
        $jiraApi = new JiraApi($this->debugLogger);

        foreach ($projects as $project){
            $this->debugLogger->message("Processing project $project");
            $versions = $jiraApi->getAllVersions($project);
            //an: Версии созданные с помощью RDS помечаются [auto]
            $versions = array_filter($versions, function($version){
                return (false !== strpos($version['description'], '[auto]')) && $version['released'] == false;
            });

            foreach ($versions as $version) {

                if ($count = JiraCommit::model()->countByAttributes([
                    'jira_commit_build_tag' => $version['name'],
                    'jira_commit_tag_created' => false,
                ])) {
                    $this->debugLogger->message("Can't release version {$version['name']} cause there are $count non-processed commits");
                    continue;
                }

                $this->debugLogger->message("Checking version {$version['name']}, id: {$version['id']}");
                $tickets = $jiraApi->getTicketsByVersion($version['id']);

                if ($tickets['issues'] === []) {
                    $this->debugLogger->message("[-] Version {$version['name']} has no tickets, removing it");
                    if (!$dryRun) $jiraApi->removeProjectVersion($version['id']);
                } else {
                    $existsNotClosed = false;
                    foreach ($tickets['issues'] as $ticket) {
                        $status = $ticket['fields']['status']['name'];
                        $this->debugLogger->message("Found {$ticket['key']} at status \"$status\"");
                        if ($status != \Jira\Status::STATUS_CLOSED) {
                            $existsNotClosed = true;
                        }
                    }

                    if (!$existsNotClosed) {
                        $this->debugLogger->message("[*] Version {$version['name']} has no non-closed tickets, releasing it");

                        $releaseRequest = null;
                        preg_match('~^(.*)-(.*?)$~', $version['name'], $ans);
                        list(,$projectName, $buildVersion) = $ans;
                        /** @var $Project Project */
                        $Project = Project::model()->findByAttributes(['project_name' => $projectName]);

                        if ($Project) {
                            /** @var $releaseRequest ReleaseRequest */
                            $releaseRequest = ReleaseRequest::model()->findByAttributes([
                                'rr_project_obj_id' => $Project->obj_id,
                                'rr_build_version' => $buildVersion,
                            ]);
                        }

                        if (!$dryRun) $jiraApi->releaseProjectVersion($version['id'], true, $releaseRequest ? $releaseRequest->obj_created : null);
                    }
                }

                $cronJob->ensureStillCanRun();
            }
        }
    }
}
