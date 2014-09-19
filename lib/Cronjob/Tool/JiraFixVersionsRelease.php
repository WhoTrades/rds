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

            //an: последнюю версию никогда не удаляем и не архивируем, потому что по ней ещё просто могла не отработать очередь создания тикетов
            array_pop($versions);

            foreach ($versions as $version) {
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
                        if ($status != 'Закрыт') {
                            $existsNotClosed = true;
                        }
                    }

                    if (!$existsNotClosed) {
                        $this->debugLogger->message("[*] Version {$version['name']} has no non-closed tickets, releasing it");
                        if (!$dryRun) $jiraApi->releaseProjectVersion($version['id']);
                    }
                }

                $cronJob->ensureStillCanRun();
            }
        }
    }
}
