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
        $c = new CDbCriteria();
        $c->compare('migration_status', [HardMigration::MIGRATION_STATUS_NEW, HardMigration::MIGRATION_STATUS_FAILED]);
        $c->compare('migration_environment', \Config::getInstance()->hardMigration['autoStartEnvironments']);
        $c->order = 'random()';

        /** @var $migrations \HardMigration[] */
        if (!$migrations = \HardMigration::model()->findAll($c)) {
            $this->debugLogger->message("Nothing to stars, exiting");
            return;
        }

        $rdsSystem = new \RdsSystem\Factory($this->debugLogger);
        foreach ($migrations as $migration) {
            $c = new CDbCriteria();
            $c->compare('migration_status', [HardMigration::MIGRATION_STATUS_IN_PROGRESS, HardMigration::MIGRATION_STATUS_STARTED]);
            $c->compare('migration_environment', $migration->migration_environment);
            $c->limit = 1;

            //an: Если есть миграции, которые сейчас выполняются - то не запускаем новую
            if (\HardMigration::model()->count($c)) {
                $this->debugLogger->message("Found working migrations, waiting");
                continue;
            }

            $model  = $rdsSystem->getMessagingRdsMsModel($migration->migration_environment);
            $this->debugLogger->message("Starting migration $migration->migration_name on env=$migration->migration_environment");

            $model->sendHardMigrationTask(new \RdsSystem\Message\HardMigrationTask($migration->migration_name, $migration->project->project_name, $migration->project->project_current_version));
            $migration->migration_status = HardMigration::MIGRATION_STATUS_STARTED;
            $migration->save(false);

            Cronjob_Tool_AsyncReader_HardMigration::sendHardMigrationUpdated($migration->obj_id);
        }
    }
}
