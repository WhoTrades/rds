<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;

class StatePending extends StateBase
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_PENDING;
    }

    /**
     * {@inheritDoc}
     */
    public function canBeApplied()
    {
        if ($this->migration->getWaitingDays() > 0) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function apply()
    {
        if (!$this->canBeApplied()) {
            \Yii::warning("Post migration {$this->migration->migration_name} is waiting {$this->migration->getWaitingDays()} days for applying");

            return;
        }

        $this->migrationService->sendApplyCommand($this->migration);

        $this->migration->obj_status_did = Migration::STATUS_STARTED_APPLICATION;
        $this->migration->save();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        \Yii::warning("Can't roll back pending migration {$this->migration->migration_name}");
    }

    /**
     * {@inheritDoc}
     */
    public function tryUpdateStatus($status)
    {
        if ($status === Migration::STATUS_APPLIED) {
            $this->migration->updateStatus($status);
        }
    }
}
