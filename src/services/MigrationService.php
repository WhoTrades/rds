<?php
/**
 * Service for managing migrations
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\services;

use whotrades\rds\models\MigrationBase;
use whotrades\rds\models\Migration as MigrationModel;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\commands\MigrateController;
use whotrades\rds\services\strategies\MigrationDefaultStrategy;
use whotrades\rds\services\strategies\MigrationStrategyInterface;
use RuntimeException;

class MigrationService
{
    /**
     * @var MigrationStrategyInterface[]
     */
    protected $migrationStrategyList;

    /**
     * @param string[] $migrationNameList
     * @param string $typeName
     * @param int $migrationCommand
     * @param Project $project
     * @param ReleaseRequest $releaseRequest
     *
     * @return void
     */
    public function createOrUpdateListByCommand(array $migrationNameList, $typeName, $migrationCommand, Project $project, ReleaseRequest $releaseRequest)
    {
        $statusId = $this->getMigrationStrategy($typeName)->getStatusIdByCommand($migrationCommand);
        foreach ($migrationNameList as $migrationName) {
            $migration = $this->getMigrationStrategy($typeName)->upsert($migrationName, $typeName, $project, $releaseRequest);
            $migration->tryUpdateStatus($statusId);
            $this->tryFillFromGit($migration);
        }
    }

    /**
     * @param MigrationBase $migration
     */
    public function tryFillFromGit(MigrationBase $migration)
    {
        // TODO Implement in real instances of RDS service
    }

    /**
     * @return void
     */
    public function applyCanBeAutoAppliedMigrations()
    {
        $migrationList = MigrationModel::getMigrationCanBeAutoAppliedList();
        foreach ($migrationList as $migration) {
            $migration->apply();
        }
    }

    /**
     * @param MigrationBase $migration
     */
    public function sendApplyCommand(MigrationBase $migration)
    {
        $this->sendCommand(MigrateController::MIGRATION_COMMAND_UP_ONE, $migration);
    }

    /**
     * @param MigrationBase $migration
     */
    public function sendRollBackCommand(MigrationBase $migration)
    {
        $this->sendCommand(MigrateController::MIGRATION_COMMAND_DOWN_ONE, $migration);
    }

    /**
     * @param string $migrationCommand // ag: @see \whotrades\rds\commands\MigrateController::MIGRATION_COMMAND_*
     * @param MigrationBase $migration
     */
    protected function sendCommand($migrationCommand, MigrationBase $migration)
    {
        $this->getMigrationStrategy($migration->getTypeName())->sendCommand($migrationCommand, $migration);
    }

    /**
     * @param string $migrationTypeName
     *
     * @return MigrationStrategyInterface
     */
    protected function getMigrationStrategy($migrationTypeName)
    {
        switch ($migrationTypeName) {
            case MigrationModel::TYPE_PRE:
            case MigrationModel::TYPE_POST:
                if (!isset($this->migrationStrategyList[$migrationTypeName])) {
                    $this->migrationStrategyList[$migrationTypeName] = new MigrationDefaultStrategy($migrationTypeName);
                }

                return $this->migrationStrategyList[$migrationTypeName];
        }

        throw new RuntimeException("Unsupported migration type '{$migrationTypeName}'");
    }
}
