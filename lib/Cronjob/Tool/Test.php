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
        $model = $this->getMessagingModel($cronJob);
        $model->sendMergeTask(new Message\Merge\Task(1, "test", "test"));
    }
}

