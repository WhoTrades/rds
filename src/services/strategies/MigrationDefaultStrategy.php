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
use \RuntimeException;

class MigrationDefaultStrategy implements MigrationStrategyInterface
{
    const COMMAND_NAME_TO_STATUS_ID_MAP = [
        MigrateController::MIGRATION_COMMAND_NEW => Migration::STATUS_PENDING,
        MigrateController::MIGRATION_COMMAND_NEW_ALL => Migration::STATUS_PENDING,
        MigrateController::MIGRATION_COMMAND_HISTORY => Migration::STATUS_APPLIED,
        MigrateController::MIGRATION_COMMAND_HISTORY_ALL => Migration::STATUS_APPLIED,
    ];

    /**
     * @var string
     */
    protected $migrationTypeName;

    /**
     * @param string $migrationTypeName
     */
    public function __construct($migrationTypeName)
    {
        $this->migrationTypeName = $migrationTypeName;
    }

    /**
     * {@inheritDoc}
     */
    public function upsert($migrationName, $typeName, Project $project, ReleaseRequest $releaseRequest)
    {
        return Migration::upsert($migrationName, $typeName, $project, $releaseRequest);
    }

    /**
     * {@inheritDoc}
     */
    public function sendCommand($migrationCommand, MigrationBase $migration)
    {
        if (!in_array($migration->getTypeName(), [Migration::TYPE_PRE, Migration::TYPE_POST])) {
            Yii::warning("Skip sending command. Unsupported migration type '{$migration->getTypeName()}'");

            return;
        }

        if ($migration->getTypeName() === Migration::TYPE_PRE) {
            $releaseRequest = $migration->releaseRequest;
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
    public function getStatusIdByCommand($command)
    {
        if (isset(static::COMMAND_NAME_TO_STATUS_ID_MAP[$command])) {
            return static::COMMAND_NAME_TO_STATUS_ID_MAP[$command];
        }

        throw new RuntimeException("No status mapped to command {$command} of type {$this->migrationTypeName}");
    }
}
