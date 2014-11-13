<?php
use RdsSystem\Message;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_HardMigrationLogChunk -vv
 */
class Cronjob_Tool_AsyncReader_HardMigrationLogChunk extends RdsSystem\Cron\RabbitDaemon
{
    const SYNC_INTERVAL = 3;

    private $logAppend = [];
    private $lastLogSync;

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

        $model->readHardMigrationLogChunk(true, function(Message\HardMigrationLogChunk $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received next log chunk: ".json_encode($message));
            $this->actionProcessHardMigrationLogChunk($message, $model);
        });
    }


    public function actionProcessHardMigrationLogChunk(Message\HardMigrationLogChunk $message, MessagingRdsMs $model)
    {
        $t = microtime(true);
        $sql = "UPDATE ".HardMigration::model()->tableName()." SET migration_log=COALESCE(migration_log, '')||:log WHERE migration_name=:name and migration_environment=:env";

        HardMigration::model()->getDbConnection()->createCommand($sql)->execute([
            'log' => $message->text,
            'name' => $message->migration,
            'env' => $model->getEnv(),
        ]);

        $id = str_replace("/", "", $message->migration)."_".$model->getEnv();

        Yii::app()->realplexor->send("migrationLogChunk_$id", ['text' => $message->text]);

        $message->accepted();

        $this->debugLogger->message("Executing log chunk got ".sprintf("%.2f", 1000 * (microtime(true) - $t))." ms");
    }
}
