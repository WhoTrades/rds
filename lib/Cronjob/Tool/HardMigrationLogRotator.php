<?php
/**
 * Скрипт удаляет часть логов миграций
 * @author Artem Naumenko
 * @example dev/services/rds/misc/tools/runner.php --tool=HardMigrationLogRotator -vv
 */
class Cronjob_Tool_HardMigrationLogRotator extends \Cronjob\Tool\ToolBase
{
    const MIGRATION_LOG_MAX_SIZE = 100000;  //an: после этого значения мы будем просто обрезать лог

    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [];
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $this->debugLogger->message("Starting log rotation");

        $sql = "UPDATE rds.hard_migration SET migration_log=SUBSTRING(migration_log FROM LENGTH(migration_log)-100000+1) WHERE LENGTH(migration_log) > 100000";

        \Yii::$app->db->createCommand($sql)->execute();
        $this->debugLogger->message("Finished log rotation");
    }
}
