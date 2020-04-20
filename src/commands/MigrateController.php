<?php
/**
 * Controller processes migrate commands
 */
namespace whotrades\rds\commands;

use yii\console\controllers\MigrateController as BaseMigrateController;
use yii\console\ExitCode;
use yii\helpers\Console;

class MigrateController extends BaseMigrateController
{
    const MIGRATION_COMMAND_NEW         = 'new';
    const MIGRATION_COMMAND_NEW_ALL     = 'new all';
    const MIGRATION_COMMAND_HISTORY     = 'history';
    const MIGRATION_COMMAND_HISTORY_ALL = 'history all';
    const MIGRATION_COMMAND_UP          = 'up';
    const MIGRATION_COMMAND_UP_ONE      = 'up-one';
    const MIGRATION_COMMAND_DOWN        = 'down';
    const MIGRATION_COMMAND_DOWN_ONE    = 'down-one';

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
