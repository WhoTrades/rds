<?php
namespace app\controllers;

use yii\console\controllers\MigrateController as BaseMigrateController;

class MigrateController extends BaseMigrateController
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->migrationTable = "migration_rds";
        parent::init();
    }
}
