<?php
/**
 * Interface for a family of migrationService strategies
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\services\strategies;

use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\MigrationBase;

interface MigrationStrategyInterface
{
    /**
     * @param string $migrationName
     * @param string $typeName
     * @param Project $project
     * @param ReleaseRequest $releaseRequest
     *
     * @return MigrationBase
     */
    public function upsert($migrationName, $typeName, Project $project, ReleaseRequest $releaseRequest);

    /**
     * @param string $migrationCommand // ag: @see \whotrades\rds\commands\MigrateController::MIGRATION_COMMAND_*
     * @param MigrationBase $migration
     */
    public function sendCommand($migrationCommand, MigrationBase $migration);

    /**
     * @param string $command
     *
     * @return int | string
     */
    public function getStatusIdByCommand($command);
}
