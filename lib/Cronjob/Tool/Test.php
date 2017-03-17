<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use app\models\ReleaseRequest;
use RdsSystem\Message;
use yii\helpers\Url;

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
        $this->debugLogger->message(Url::to(['build/view', 'id' => 12], true));
    }
}
