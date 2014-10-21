<?php
use RdsSystem\Message;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=HardMigrationStarter -vv
 */
class Cronjob_Tool_HardMigrationStarter extends RdsSystem\Cron\RabbitDaemon
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array() + parent::getCommandLineSpec();
    }


    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $c = new CDbCriteria();
        $c->compare('migration_status', [HardMigration::MIGRATION_STATUS_NEW, HardMigration::MIGRATION_STATUS_FAILED]);
        $c->limit = 1;

        /** @var $migration \HardMigration */
        if (!$migration = \HardMigration::model()->find($c)) {
            $this->debugLogger->message("Nothing to stars, exiting");
            return;
        }

        $model->sendHardMigrationTask(new \RdsSystem\Message\HardMigrationTask($migration->migration_name, $migration->project->project_name, $migration->project->project_current_version));

        Cronjob_Tool_AsyncReader_HardMigration::sendHardMigrationUpdated($migration->obj_id);
    }
}
