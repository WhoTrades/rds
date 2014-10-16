<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */
class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $rdsSystem = new RdsSystem\Factory($this->debugLogger);
        $workerName = \Config::getInstance()->workerName;
        $model  = $rdsSystem->getMessagingRdsMsModel();
        $migrations = ["Y2014_2/m140804_121502_rds_test #WTA-67"];
        $model->sendMigrations(new Message\ReleaseRequestMigrations('service-mailer', '62.00.042.42', $migrations, 'hard'));
    }
}
