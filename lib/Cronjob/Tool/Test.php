<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use app\models\Build;
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
        $build = Build::findByPk(810);

        if ($build->releaseRequest && $build->releaseRequest->countNotFinishedBuilds() == 0) {
            $builds = $build->releaseRequest->builds;
            $build->releaseRequest->rr_status = ReleaseRequest::STATUS_NEW;
            $build->releaseRequest->rr_built_time = date("r");
            $build->releaseRequest->save();
            var_dump($build->releaseRequest->rr_status);
        }
    }
}
