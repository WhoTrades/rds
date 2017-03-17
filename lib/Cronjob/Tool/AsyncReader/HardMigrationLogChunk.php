<?php

use RdsSystem\Message;
use app\models\HardMigration;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_HardMigrationLogChunk -vv
 */
class Cronjob_Tool_AsyncReader_HardMigrationLogChunk extends RdsSystem\Cron\RabbitDaemon
{
    const MAX_TEXT_SIZE = 8000;
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

        $model->readHardMigrationLogChunk(false, function (Message\HardMigrationLogChunk $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received next log chunk: " . json_encode($message));
            $this->actionProcessHardMigrationLogChunk($message, $model);
        });

        $this->debugLogger->message("Start listening");
        $this->waitForMessages($model, $cronJob);
    }

    /**
     * @param Message\HardMigrationLogChunk $message
     * @param MessagingRdsMs $model
     */
    public function actionProcessHardMigrationLogChunk(Message\HardMigrationLogChunk $message, MessagingRdsMs $model)
    {
        $t = microtime(true);
        $sql = "UPDATE " . HardMigration::tableName() . " SET migration_log=COALESCE(migration_log, '')||:log WHERE migration_name=:name and migration_environment=:env";

        \Yii::$app->db->createCommand($sql, [
            'log' => $message->text,
            'name' => $message->migration,
            'env' => $model->getEnv(),
        ])->execute();

        $id = str_replace("/", "", $message->migration) . "_" . $model->getEnv();
        $this->debugLogger->message("id=$id");

        // an: Максимальный размер пакета, который умещается в comet - 8KB. Потому и нам нужно разбивать
        foreach (str_split($message->text, self::MAX_TEXT_SIZE) as $chunk) {
            Yii::$app->webSockets->send("migrationLogChunk_$id", ['text' => $chunk]);
        }

        $message->accepted();

        $this->debugLogger->message("Executing log chunk got " . sprintf("%.2f", 1000 * (microtime(true) - $t)) . " ms");
    }
}
