<?php
namespace app\commands;

use yii\console\controllers\MigrateController as BaseMigrateController;

class MigrateController extends BaseMigrateController
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->migrationTable = "rds.migration_rds";
    }
}
