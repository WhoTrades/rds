<?php

use RdsSystem\Message;
use app\modules\Wtflow\models\HardMigration;
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
     * @param \Cronjob\ICronjob $cronJob
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model = $this->getMessagingModel($cronJob);

        $model->readHardMigrationProgress(false, function (Message\HardMigrationProgress $message) use ($model) {
            Yii::info("env={$model->getEnv()}, Received harm migration progress changed: " . json_encode($message));
            $this->actionHardMigrationProgressChanged($message, $model);
        });

        Yii::info("Start listening");
        $this->waitForMessages($model, $cronJob);
    }

    /**
     * @param Message\HardMigrationProgress $message
     * @param MessagingRdsMs $model
     */
    public function actionHardMigrationProgressChanged(Message\HardMigrationProgress $message, MessagingRdsMs $model)
    {
        $message->accepted();
        $t = microtime(true);
        $sql = "UPDATE " . HardMigration::tableName() . "
        SET migration_progress=:progress, migration_progress_action=:action, migration_pid=:pid
        WHERE migration_name=:name and migration_environment=:env";

        \Yii::$app->db->createCommand($sql, [
            'progress' => $message->progress,
            'action' => $message->action,
            'pid' => $message->pid,
            'name' => $message->migration,
            'env' => $model->getEnv(),
        ])->execute();

        $this->sendMigrationProgressbarChanged(str_replace("/", "", "{$message->migration}_{$model->getEnv()}"), $message->progress, $message->action);

        Yii::info("Progress of migration $message->migration updated ($message->progress%)");
        Yii::info("Executing progress got " . sprintf("%.2f", 1000 * (microtime(true) - $t)) . " ms");
    }

    private function sendMigrationProgressbarChanged($id, $percent, $key)
    {
        Yii::info("Sending migraion progressbar to comet");
        Yii::$app->webSockets->send('migrationProgressbarChanged', ['migration' => $id, 'percent' => (float) $percent, 'key' => $key]);
    }
}
