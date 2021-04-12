<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

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
    public function apply()
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
        if ($status === Migration::STATUS_PENDING) {
            $this->migration->updateStatus($status);
        }
    }
}
