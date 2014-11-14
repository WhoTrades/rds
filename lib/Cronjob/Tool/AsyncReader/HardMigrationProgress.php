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
        $model  = $this->getMessagingModel($cronJob);

        $model->readHardMigrationProgress(false, function(Message\HardMigrationProgress $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received harm migration progress changed: ".json_encode($message));
            $this->actionHardMigrationProgressChanged($message, $model);
        });

        $this->debugLogger->message("Start listening");
        $this->waitForMessages($model, $cronJob);
    }


    public function actionHardMigrationProgressChanged(Message\HardMigrationProgress $message, MessagingRdsMs $model)
    {
        $message->accepted();
        $t = microtime(true);
        $sql = "UPDATE ".HardMigration::model()->tableName()."
        SET migration_progress=:progress, migration_progress_action=:action, migration_pid=:pid
        WHERE migration_name=:name and migration_environment=:env";

        \HardMigration::model()->getDbConnection()->createCommand($sql)->execute([
            'progress' => $message->progress,
            'action' => $message->action,
            'pid' => $message->pid,
            'name' => $message->migration,
            'env' => $model->getEnv(),
        ]);

        $this->sendMigrationProgressbarChanged(str_replace("/", "", "{$message->migration}_{$model->getEnv()}"), $message->progress, $message->action);

        $this->debugLogger->message("Progress of migration $message->migration updated ($message->progress%)");
        $this->debugLogger->message("Executing progress got ".sprintf("%.2f", 1000 * (microtime(true) - $t))." ms");

    }

    private function sendMigrationProgressbarChanged($id, $percent, $key)
    {
        $this->debugLogger->message("Sending migraion progressbar to comet");
        Yii::app()->realplexor->send('migrationProgressbarChanged', ['migration' => $id, 'percent' => (float)$percent, 'key' => $key]);
    }
}
