<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\ReleaseRequest;
use Yii;
use whotrades\rds\models\Migration;

class StateDeleted extends StateBase
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_DELETED;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(ReleaseRequest $releaseRequest = null)
    {
        Yii::warning("Can't apply deleted migration {$this->migration->migration_name}");
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        Yii::warning("Can't roll back deleted migration {$this->migration->migration_name}");
    }

    /**
     * {@inheritDoc}
     */
    public function tryUpdateStatus($status)
    {
        if (in_array($status, [Migration::STATUS_PENDING, Migration::STATUS_APPLIED])) {
            $this->migration->updateStatus($status);
        }
    }
}
