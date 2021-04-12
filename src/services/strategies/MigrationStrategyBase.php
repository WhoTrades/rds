<?php
/**
 * Interface for a family of migrationService strategies
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\services\strategies;

use Yii;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\MigrationBase;
use RuntimeException;

abstract class MigrationStrategyBase
{
    const MIGRATION_LIMIT = 10;
    const COMMAND_NAME_TO_STATUS_ID_MAP = [];

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
     * @param string $migrationName
     * @param ReleaseRequest $releaseRequest
     *
     * @return MigrationBase
     */
    abstract public function upsert($migrationName, ReleaseRequest $releaseRequest): MigrationBase;

    /**
     * @param string $migrationCommand // ag: @see \whotrades\rds\commands\MigrateController::MIGRATION_COMMAND_*
     * @param MigrationBase $migration
     */
    abstract public function sendCommand($migrationCommand, MigrationBase $migration): void;

    /**
     * @param array $existentMigrationNameList
     * @param Project $project
     *
     * @return void
     */
    public function deleteNonExistentMigrations(array $existentMigrationNameList, Project $project): void
    {
        $objIdFilter = PHP_INT_MAX;
        while ($migrationList = $this->getMigrationList($project, $objIdFilter, self::MIGRATION_LIMIT)) {
            /** @var MigrationBase $migration */
            foreach ($migrationList as $migration) {
                if (!in_array($migration->migration_name, $existentMigrationNameList)) {
                    Yii::info("Delete migration {$migration->migration_name}");
                    $migration->setStatusDeleted();
                }
            }
            $objIdFilter = $migrationList[array_key_last($migrationList)]->obj_id;
        };
    }

    /**
     * @param string $command
     *
     * @return int | string
     */
    public function getStatusIdByCommand($command)
    {
        if (isset(static::COMMAND_NAME_TO_STATUS_ID_MAP[$command])) {
            return static::COMMAND_NAME_TO_STATUS_ID_MAP[$command];
        }

        throw new RuntimeException("No status mapped to command {$command} of type {$this->migrationTypeName}");
    }

    /**
     * @param Project $project
     * @param int $objIdFilter
     * @param int $limit
     *
     * @return MigrationBase[]
     */
    abstract protected function getMigrationList(Project $project, int $objIdFilter, int $limit): array;
}
