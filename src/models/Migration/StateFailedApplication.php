<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
namespace whotrades\rds\models\Migration;

use whotrades\rds\models\Migration;

class StateFailedApplication extends StatePending
{
    /**
     * {@inheritDoc}
     */
    public function getStatusId()
    {
        return Migration::STATUS_FAILED_APPLICATION;
    }
}
