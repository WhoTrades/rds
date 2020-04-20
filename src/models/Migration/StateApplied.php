<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;

class StateApplied extends StateBase
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_APPLIED;
    }

    /**
     * {@inheritDoc}
     */
    public function canBeRolledBack()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function apply()
    {
        \Yii::info("Migration {$this->migration->migration_name} is applied already");
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        $this->migrationService->sendRollBackCommand($this->migration);

        $this->migration->obj_status_did = Migration::STATUS_STARTED_ROLLBACK;
        $this->migration->save();
    }

    /**
     * {@inheritDoc}
     */
    public function tryUpdateStatus($status)
    {
        if ($status === Migration::STATUS_PENDING) {
            $this->migration->updateStatus($status);
        }
    }
}
