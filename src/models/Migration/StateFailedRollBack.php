<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;

class StateFailedRollBack extends StateApplied
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_FAILED_ROLLBACK;
    }
}
