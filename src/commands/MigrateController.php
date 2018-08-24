<?php

namespace whotrades\rds\commands;

use yii\console\controllers\MigrateController as BaseMigrateController;
use yii\console\ExitCode;
use yii\helpers\Console;

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

    /**
     * @param string $migration
     *
     * @return int
     */
    public function actionUpOne($migration)
    {
        if (!$this->migrateUp($migration)) {
            $this->stdout("\nMigration failed.\n", Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("\nMigrated up successfully.\n", Console::FG_GREEN);
    }

    /**
     * @param string $migration
     *
     * @return int
     */
    public function actionDownOne($migration)
    {
        if (!$this->migrateDown($migration)) {
            $this->stdout("\nMigration failed.\n", Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("\nMigrated down successfully.\n", Console::FG_GREEN);
    }
}
