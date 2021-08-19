<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\ReleaseRequest;
use Yii;
use whotrades\rds\models\Migration;
use whotrades\rds\services\MigrationService;

abstract class StateBase
{
    /**
     * @var Migration
     */
    protected $migration;

    /**
     * @var MigrationService
     */
    protected $migrationService;

    /**
     * @param Migration $migration
     */
    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
        $this->migrationService = Yii::$app->migrationService;
    }

    /**
     * @return int
     */
    abstract public function getStatusId();

    /**
     * @param ReleaseRequest|null $releaseRequest
     *
     * @return void
     */
    abstract public function apply(ReleaseRequest $releaseRequest = null);

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
        Yii::error("Can't be succeed. Migration {$this->migration->migration_name} is in status '{$this->migration->getStatusName()}'");
    }

    /**
     * @return void
     */
    public function failed()
    {
        Yii::error("Can't be failed. Migration {$this->migration->migration_name} is in status '{$this->migration->getStatusName()}'");
    }
}
