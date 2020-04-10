<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;

class StateStartedApplication extends StateBase
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_STARTED_APPLICATION;
    }

    /**
     * {@inheritDoc}
     */
    public function apply()
    {
        \Yii::info("Application of migration {$this->migration->migration_name} is started already");
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        \Yii::warning("Application of migration {$this->migration->migration_name} is started already");
    }

    /**
     * {@inheritDoc}
     */
    public function succeed()
    {
        $this->migration->obj_status_did = Migration::STATUS_APPLIED;
        $this->migration->save();
    }

    /**
     * {@inheritDoc}
     */
    public function failed()
    {
        $this->migration->obj_status_did = Migration::STATUS_FAILED_APPLICATION;
        $this->migration->save();
    }
}
