<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use app\models\Build;
use app\models\ReleaseRequest;
use app\modules\Wtflow\models\JiraFeature;
use CompanyInfrastructure\WtTeamCityClient;
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
     * @throws phpmailerException
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $res = parse_url(\Yii::$app->sentry->dsn);
        $url = "{$res['scheme']}://{$res['host']}/";
        $api = new \CompanyInfrastructure\SentryApi($url);
        $list = iterator_to_array($api->getNewFatalErrorsIterator('sentry', 'comon', 'dev'));

        var_export($list);
    }
}
