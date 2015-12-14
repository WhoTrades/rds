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
        $ids = [
            'WhoTrades_AcceptanceTests_JUnit4comonAddPostText',
            'WhoTrades_AcceptanceTests_JUnit4comonLoginUSmarkets',
            'WhoTrades_AcceptanceTests_JUnit4comonOpenAccounts',
//            'WhoTrades_AcceptanceTests_JUnit4comonRegisrationPopapStrategies',
//            'WhoTrades_AcceptanceTests_JUnit4comonRegistrationForex',
//            'WhoTrades_AcceptanceTests_JUnit4comon_2',
//            'WhoTrades_AcceptanceTests_JUnit4comonRegistrationUsmarkets',
            'WhoTrades_AcceptanceTests_JUnit4comonTest101Registration',
        ];
        $teamcity = new \CompanyInfrastructure\WtTeamCityClient();

        foreach ($ids as $buildTypeId) {
            $result = $teamcity->startBuild($buildTypeId, 'master', null, [
                'env.ENV' => 'dev',
                'env.DLD' => 'pdev',
            ]);

            $this->debugLogger->message("Started build " . $result->attributes()['webUrl']);
        }



        return;

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
