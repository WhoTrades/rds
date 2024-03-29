<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;
use whotrades\rds\models\ReleaseRequest;

class StateStartedRollBack extends StateBase
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_STARTED_ROLLBACK;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(ReleaseRequest $releaseRequest = null)
    {
        \Yii::warning("Roll back of migration {$this->migration->migration_name} is started already");
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        \Yii::info("Roll back of migration {$this->migration->migration_name} is started already");
    }

    /**
     * {@inheritDoc}
     */
    public function succeed()
    {
        $this->migration->obj_status_did = Migration::STATUS_PENDING;
        $this->migration->save();
    }

    /**
     * {@inheritDoc}
     */
    public function failed()
    {
        $this->migration->obj_status_did = Migration::STATUS_FAILED_ROLLBACK;
        $this->migration->save();
    }
}
