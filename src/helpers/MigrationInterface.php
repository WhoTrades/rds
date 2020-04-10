<?php

namespace whotrades\rds\helpers;

use whotrades\rds\models\MigrationBase;


interface MigrationInterface
{
    /**
     * @param MigrationBase $migration
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getWaitingDays(MigrationBase $migration);

    /**
     * @param MigrationBase $migration
     *
     * @return void
     */
    public function fillFromGit(MigrationBase $migration);
}