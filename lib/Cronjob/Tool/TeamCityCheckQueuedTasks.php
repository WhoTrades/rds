<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=TeamCityCheckQueuedTasks -vv
 */

use RdsSystem\Message;

class Cronjob_Tool_TeamCityCheckQueuedTasks extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $this->debugLogger->message("Starting");
        $teamcity = new CompanyInfrastructure\WtTeamCityClient();
        $builds = TeamcityBuild::model()->findAllByAttributes([
            'tb_status' => TeamCityBuild::STATUS_QUEUED,
            'tb_notified' => false,
        ]);
        foreach ($builds as $build) {
            /** @var $build TeamCityBuild */
            $this->debugLogger->message("Checking queued build #{$build->getQueuedId()}, http://ci.whotrades.net:8111/viewQueued.html?itemId={$build->getQueuedId()}");
            $info = $teamcity->getQueuedBuildInfo($build->getQueuedId());
            if ($info['state'] == 'finished') {
                $this->debugLogger->message("Status of build is finished, pushing event to queue");
                $tbc = new TeamcityBuildComplete();
                $tbc->attributes = [
                    'tbc_build_id'      => $info['id'],
                    'tbc_branch'        => $info['branchName'],
                    'tbc_build_type_id' => $info['buildTypeId'],
                ];
                $tbc->save();
                $build->tb_notified = true;
                $build->save();
            } else {
                $this->debugLogger->message("Status of build is {$info['state']}, skip it");
            }
        }
        $this->debugLogger->message("Finished");
    }
}

