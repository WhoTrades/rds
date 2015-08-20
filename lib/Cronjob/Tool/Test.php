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
        $pullRequestTitle = 'test';
        $pullRequestDescription = 'test';
        $pullRequestProject = 'WT';
        $pullRequestRepo = 'sparta';
        $fromBranch = 'staging';
        $toBranch = 'master';
        $assignee = ['anaumenko'];
        $stash = new \CompanyInfrastructure\StashApi($this->debugLogger);
        $data = $stash->createPullRequest($pullRequestTitle, $pullRequestDescription, $pullRequestProject, $pullRequestRepo, $fromBranch, $toBranch, $assignee);
        $url = "http://stash".$data['link']['url'];

        $this->debugLogger->message($url);

        return;


        $jira = new \JiraApi($this->debugLogger);
        $jira->setCustomField('WTTES-47', 'customfield_15000', 'http://lenta.ru/');
    }
}
