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
        $tag = 'activity_stream_queue';
        $model = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel();

        $res = $model->sendToolGetToolLogTail(
            new RdsSystem\Message\Tool\ToolLogTail($tag, 10), RdsSystem\Message\Tool\ToolLogTailResult::type(), 1
        );
    }
}
