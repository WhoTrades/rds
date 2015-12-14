<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $teamcity = new \CompanyInfrastructure\WtTeamCityClient();
        $project = $teamcity->getProject('WhoTrades_AcceptanceTests');
        foreach ($project->buildTypes->children() as $buildType) {
            /** @var $buildType SimpleXmlElement */
            $buildTypeId = $buildType->attributes()['id'];
            $build = $teamcity->getLastBuildByBuildType($buildTypeId);
            $status = (string) $build->attributes()['status'];
            $url = (string) $build->attributes()['webUrl'];

            if ($status === 'SUCCESS') {
                $this->debugLogger->message($url);

                $result = $teamcity->startBuild($buildTypeId, 'master', null, [
                    'env.ENV' => 'dev',
                    'env.DLD' => 'pdev',
                ]);

                $this->debugLogger->message("Started build " . $result->attributes()['webUrl']);
            }
        }
    }
}
