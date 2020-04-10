<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Build;
use whotrades\RdsSystem\Message;
use whotrades\RdsSystem\Factory as RdsSystemFactory;

abstract class StateBase
{
    /**
     * @var Migration
     */
    protected $migration;

    /**
     * @param Migration $migration
     */
    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * @return int
     */
    abstract public function getStatusId();

    /**
     * @return void
     */
    abstract public function apply();

    /**
     * @return void
     */
    abstract public function rollBack();

    /**
     * @param int $status
     *
     * @throws \Exception
     */
    public function tryUpdateStatus($status)
    {
        return;
    }

    /**
     * @return bool
     */
    public function canBeApplied()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canBeRolledBack()
    {
        return false;
    }

    /**
     * @return void
     */
    public function succeed()
    {
        \Yii::error("Can't be succeed. Migration {$this->migration->migration_name} is in status '{$this->migration->getStatusName()}'");
    }

    /**
     * @return void
     */
    public function failed()
    {
        \Yii::error("Can't be failed. Migration {$this->migration->migration_name} is in status '{$this->migration->getStatusName()}'");
    }

    /**
     * @param string $migrationCommand // ag: @see \whotrades\rds\commands\MigrateController::MIGRATION_COMMAND_*
     */
    protected function sendCommand($migrationCommand)
    {
        if ($this->migration->migration_type === Migration::TYPE_ID_PRE) {
            $releaseRequest = $this->migration->releaseRequest;
        } else {
            $releaseRequest = ReleaseRequest::getUsedReleaseByProjectId($this->migration->project->obj_id);
        }

        $messagingModel = (new RdsSystemFactory())->getMessagingRdsMsModel();

        /** @var Build $build */
        foreach ($releaseRequest->builds as $build) {
            $messagingModel->sendMigrationTask(
                $build->worker->worker_name,
                new Message\MigrationTask(
                    $releaseRequest->project->project_name,
                    $releaseRequest->rr_build_version,
                    $this->migration->getTypeName(),
                    $this->migration->project->script_migration_up,
                    $migrationCommand,
                    $this->migration->migration_name
                )
            );
        }
    }
}
