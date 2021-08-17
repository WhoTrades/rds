<?php
/**
 * Service for managing migrations
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\services;

use Yii;
use whotrades\rds\models\MigrationBase;
use whotrades\rds\models\Migration as MigrationModel;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\commands\MigrateController;
use whotrades\rds\services\strategies\MigrationDefaultStrategy;
use whotrades\rds\services\strategies\MigrationStrategyBase;
use RuntimeException;
use whotrades\RdsSystem\lib\CommandExecutor;
use whotrades\RdsSystem\lib\Exception\CommandExecutorException;

class MigrationService
{
    /**
     * @var MigrationStrategyBase[]
     */
    protected $migrationStrategyList;

    /**
     * @param string $typeName
     * @param ReleaseRequest $releaseRequest
     *
     * @return void
     */
    public function addOrUpdateExistedMigrations(string $typeName, ReleaseRequest $releaseRequest)
    {
        foreach ([MigrateController::MIGRATION_COMMAND_NEW_ALL, MigrateController::MIGRATION_COMMAND_HISTORY] as $migrationCommand) {
            $migrationNameList = $this->getMigrationNameList($migrationCommand, $typeName, $releaseRequest);
            $this->addOrUpdateListByCommand($migrationNameList, $migrationCommand, $typeName, $releaseRequest);
        }
    }

    /**
     * @param array $migrationNameList
     * @param string $migrationCommand
     * @param string $typeName
     * @param ReleaseRequest $releaseRequest
     *
     * @throws \Exception
     */
    public function addOrUpdateListByCommand(array $migrationNameList, string $migrationCommand, string $typeName, ReleaseRequest $releaseRequest)
    {
        $statusId = $this->getMigrationStrategy($typeName)->getStatusIdByCommand($migrationCommand);
        foreach ($migrationNameList as $migrationName) {
            $migration = $this->getMigrationStrategy($typeName)->upsert($migrationName, $releaseRequest);
            $migration->tryUpdateStatus($statusId);
            $this->tryFillFromGit($migration);
        }
    }

    /**
     * @param string $typeName
     * @param ReleaseRequest $releaseRequest
     *
     * @return void
     */
    public function deleteNonExistentMigrations(string $typeName, ReleaseRequest $releaseRequest)
    {
        $existentMigrationNameList = array_merge(
            $this->getMigrationNameList(MigrateController::MIGRATION_COMMAND_NEW_ALL, $typeName, $releaseRequest),
            $this->getMigrationNameList(MigrateController::MIGRATION_COMMAND_HISTORY_ALL, $typeName, $releaseRequest)
        );

        if (!$existentMigrationNameList) {
            Yii::warning("There are not existent new and applied migrations in project {$releaseRequest->project->project_name}.");
            Yii::warning("It is possible an error of executing a migration script on project");
            Yii::warning("Skip deleting all migrations.");

            return;
        }

        $this->getMigrationStrategy($typeName)->deleteNonExistentMigrations($existentMigrationNameList, $releaseRequest->project);
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
     * @return MigrationStrategyBase
     */
    protected function getMigrationStrategy($migrationTypeName)
    {
        if (!in_array($migrationTypeName, [MigrationModel::TYPE_PRE, MigrationModel::TYPE_POST])) {
            throw new RuntimeException("Unsupported migration type '{$migrationTypeName}'");
        }

        if (!isset($this->migrationStrategyList[$migrationTypeName])) {
            $this->migrationStrategyList[$migrationTypeName] = new MigrationDefaultStrategy($migrationTypeName);
        }

        return $this->migrationStrategyList[$migrationTypeName];
    }

    /**
     * @param $migrationCommand
     * @param $typeName
     * @param ReleaseRequest $releaseRequest
     *
     * @return array
     *
     * @throws CommandExecutorException
     */
    protected function getMigrationNameList($migrationCommand, $typeName, ReleaseRequest $releaseRequest): array
    {
        $project = $releaseRequest->project;
        $text = $this->executeScript(
            $project->script_migration_new,
            "/tmp/migration-{$typeName}-script-",
            [
                'project' => $project->project_name,
                'version' => $releaseRequest->rr_build_version,
                'type' => $typeName,
                'command' => $migrationCommand,
            ]
        );

        $lines = explode("\n", str_replace("\r", "", $text));
        $migrationNameList = array_filter($lines);
        $migrationNameList = array_map('trim', $migrationNameList);
        $migrationNameList = array_unique($migrationNameList);

        return $migrationNameList;
    }


    /**
     * @param string $script
     * @param string $scriptPrefix
     * @param array $env
     *
     * @return string
     *
     * @throws CommandExecutorException
     */
    protected function executeScript($script, $scriptPrefix, array $env)
    {
        $commandExecutor = new CommandExecutor();

        $scriptFilename = $scriptPrefix . uniqid() . ".sh";
        file_put_contents($scriptFilename, str_replace("\r", "", $script));
        chmod($scriptFilename, 0777);

        $result = $commandExecutor->executeCommand("$scriptFilename 2>&1", $env);

        unlink($scriptFilename);

        return $result;
    }
}
