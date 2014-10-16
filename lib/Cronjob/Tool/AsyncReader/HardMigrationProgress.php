<?php
use RdsSystem\Message;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_HardMigrationProgress -vv
 */
class Cronjob_Tool_AsyncReader_HardMigrationProgress extends RdsSystem\Cron\RabbitDaemon
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array() + parent::getCommandLineSpec();
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $rdsSystem = new RdsSystem\Factory($this->debugLogger);
        $model  = $rdsSystem->getMessagingRdsMsModel();

        $model->readHardMigrationProgress(false, function(Message\HardMigrationProgress $message) use ($model) {
            $this->debugLogger->message("Received harm migration progress changed: ".json_encode($message));
            $this->actionHardMigrationProgressChanged($message);
        });

        $this->debugLogger->message("Start listening");

        $this->waitForMessages($model, $cronJob);
    }


    public function actionHardMigrationProgressChanged(Message\HardMigrationProgress $message)
    {
        $message->accepted();
        /** @var $migration HardMigration */
        if (!$migration = HardMigration::model()->findByAttributes(['migration_name' => $message->migration])) {
            $this->debugLogger->error("Can't find migration $message->migration");
            return;
        }
        $migration->migration_progress = $message->progress;
        $migration->migration_progress_action = $message->action;
        $migration->migration_pid = $message->pid;
        $migration->save(false);

        $this->sendMigrationProgressbarChanged($migration->obj_id, $migration->migration_progress, $migration->migration_progress_action);

        $this->debugLogger->message("Progress of migration $message->migration updated ($message->progress%)");

    }

    private function sendMigrationProgressbarChanged($id, $percent, $key)
    {
        $this->debugLogger->message("Sending migraion progressbar to comet");
        Yii::app()->realplexor->send('migrationProgressbarChanged', ['migration' => $id, 'percent' => (float)$percent, 'key' => $key]);
    }

    public function createUrl($route, $params)
    {
        return Yii::app()->createUrl($route, $params);
    }
}
