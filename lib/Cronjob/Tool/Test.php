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
        $id = 728;
        /** @var $debugLogger \ServiceBase_IDebugLogger */
        $debugLogger = \Yii::$app->debugLogger;

        if (!$releaseRequest = app\models\ReleaseRequest::findByPk($id)) {
            return;
        }

        $debugLogger->message("Sending to comet new data of releaseRequest $id");

        $html = \Yii::$app->view->renderFile('@app/views/site/_releaseRequestGrid.php', [
                'dataProvider' => $releaseRequest->search(['obj_id' => $id]),
        ]);

        \Yii::$app->webSockets->send('releaseRequestChanged', ['rr_id' => $id, 'html' => $html]);
        $debugLogger->message("Sended");
    }
}
