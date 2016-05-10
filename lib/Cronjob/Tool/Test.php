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
        $c = new CompanyInfrastructure\WtTeamCityClient($this->debugLogger);
        $list = $c->startBuild('WhoTrades_WhoTradesPHPUnit_Phpunit', 'master', 'test');
        var_export($list);
    }
}
