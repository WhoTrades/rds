<?php
/**
 * Default migrationService strategy
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\services\strategies;

use Yii;
use whotrades\rds\models\Migration;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Build;
use whotrades\rds\models\MigrationBase;
use whotrades\RdsSystem\Message;
use whotrades\RdsSystem\Factory as RdsSystemFactory;
use whotrades\rds\commands\MigrateController;

class MigrationDefaultStrategy extends MigrationStrategyBase
{
    const COMMAND_NAME_TO_STATUS_ID_MAP = [
        MigrateController::MIGRATION_COMMAND_NEW => Migration::STATUS_PENDING,
        MigrateController::MIGRATION_COMMAND_NEW_ALL => Migration::STATUS_PENDING,
        MigrateController::MIGRATION_COMMAND_HISTORY => Migration::STATUS_APPLIED,
        MigrateController::MIGRATION_COMMAND_HISTORY_ALL => Migration::STATUS_APPLIED,
    ];

    /**
     * {@inheritDoc}
     */
    public function upsert($migrationName, ReleaseRequest $releaseRequest): MigrationBase
    {
        return Migration::upsert($migrationName, $this->migrationTypeName, $releaseRequest);
    }

    /**
     * {@inheritDoc}
     */
    public function sendCommand($migrationCommand, MigrationBase $migration, ReleaseRequest $releaseRequest = null): void
    {
        if (!in_array($migration->getTypeName(), [Migration::TYPE_PRE, Migration::TYPE_POST])) {
            Yii::warning("Skip sending command. Unsupported migration type '{$migration->getTypeName()}'");

            return;
        }

        if ($migration->getTypeName() === Migration::TYPE_PRE) {
            $releaseRequest = $releaseRequest ?? $migration->releaseRequest;
        } else {
            $releaseRequest = ReleaseRequest::getUsedReleaseByProjectId($migration->project->obj_id);
        }

        $messagingModel = (new RdsSystemFactory())->getMessagingRdsMsModel();

        /** @var Build $build */
        foreach ($releaseRequest->builds as $build) {
            $messagingModel->sendMigrationTask(
                $build->worker->worker_name,
                new Message\MigrationTask(
                    $releaseRequest->project->project_name,
                    $releaseRequest->rr_build_version,
                    $migration->getTypeName(),
                    $migration->project->script_migration_up,
                    $migrationCommand,
                    $migration->migration_name
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getMigrationList(Project $project, int $objIdFilter, int $limit): array
    {
        return Migration::findNotDeletedWithLimit($this->migrationTypeName, $project, $objIdFilter, $limit);
    }
}
