<?php

namespace whotrades\rds\helpers;

use Yii;
use whotrades\rds\models\MigrationBase;
use whotrades\rds\models\Migration as MigrationModel;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use app\modules\Wtflow\models\HardMigration;
use whotrades\rds\commands\MigrateController;
use DateTime;
use RuntimeException;

class Migration implements MigrationInterface
{
    public static $commandNameToStatusIdMap = [
        MigrateController::MIGRATION_COMMAND_NEW => MigrationModel::STATUS_PENDING,
        MigrateController::MIGRATION_COMMAND_NEW_ALL => MigrationModel::STATUS_PENDING,
        MigrateController::MIGRATION_COMMAND_HISTORY => MigrationModel::STATUS_APPLIED,
    ];

    public static function getStatusIdByCommand($command)
    {
        if (!isset(self::$commandNameToStatusIdMap[$command])) {
            throw new RuntimeException("No status mapped to command {$command}");
        }

        return self::$commandNameToStatusIdMap[$command];
    }

    /**
     * @param string[] $migrationNameList
     * @param string $typeName
     * @param int $migrationCommand
     * @param Project $project
     * @param ReleaseRequest $releaseRequest
     *
     * @return void
     */
    public static function createOrUpdateListByCommand(array $migrationNameList, $typeName, $migrationCommand, Project $project, ReleaseRequest $releaseRequest)
    {
        $statusId = self::getStatusIdByCommand($migrationCommand);

        switch ($typeName) {
            case MigrationModel::TYPE_PRE:
            case MigrationModel::TYPE_POST:
                MigrationModel::createOrUpdateList($migrationNameList, $typeName, $statusId, $project, $releaseRequest);
                break;
            case MigrationModel::TYPE_HARD:
                if (Yii::$app->hasModule('Wtflow')) {
                    HardMigration::createOrUpdateList($migrationNameList, $typeName, $statusId, $project, $releaseRequest);
                }
        }
    }

    /**
     * @param MigrationBase $migration
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getWaitingDays(MigrationBase $migration)
    {
        $postMigrationAllowTimestamp = strtotime("-" . Yii::$app->params['postMigrationStabilizeDelay']);
        $waitingTime = (new DateTime($migration->obj_created))->getTimestamp() - $postMigrationAllowTimestamp;

        if ($waitingTime <= 0) {
            return 0;
        }

        return ceil($waitingTime / (24 * 60 * 60));
    }

    public function fillFromGit(MigrationBase $migration)
    {
        // TODO Implement in real instances of RDS service
    }
}