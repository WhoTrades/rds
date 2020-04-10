<?php

namespace whotrades\rds\helpers;

use Yii;
use whotrades\rds\models\MigrationBase;


class Migration implements MigrationInterface
{
    /**
     * @param MigrationBase $migration
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getWaitingDays(MigrationBase $migration)
    {
        $postMigrationAllowTimestamp = strtotime("-" . Yii::$app->params['postMigrationStabilizeDelay']);
        $waitingTime = (new \DateTime($migration->obj_created))->getTimestamp() - $postMigrationAllowTimestamp;

        if ($waitingTime <= 0) {
            return 0;
        }

        return ceil($waitingTime / (24 * 60 * 60));
    }

    public function fillFromGit(MigrationBase $migration)
    {
        // TODO Implement in real instances of RDS service
    }
}