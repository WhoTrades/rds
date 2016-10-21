<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     *
     * @throws CException
     * @throws phpmailerException
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel();

        $res = $model->sendToolGetToolLogTail(
            'debian',
            new RdsSystem\Message\Tool\ToolLogTail('test', 100),
            RdsSystem\Message\Tool\ToolLogTailResult::type(),
            1
        );
        var_dump($res);
    }
}
