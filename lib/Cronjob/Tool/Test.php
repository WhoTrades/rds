<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;
use RdsSystem\lib\CommandExecutor;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    private $teamCityClient;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $this->teamCityClient = new \CompanyInfrastructure\WtTeamCityClient();


        $dataProvider = new \AlertLog\TeamCityDataProvider($this->debugLogger, $this->teamCityClient, 'WhoTrades_AcceptanceTests');

        var_dump(array_filter($dataProvider->getData(), function(\AlertLog\AlertData $alertData) {
            return strpos($alertData->getName(), 'JUnit4_comon_Login_popap_blogs') > 0;
        }));
    }
}
