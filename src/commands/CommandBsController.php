<?php
/**
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\commands;

class CommandBsController extends \whotrades\RdsSystem\commands\CommandController
{
    /**
     * @return array
     */
    public function getCommands()
    {
        return [
            $this->createCommand(MigrationController::class, 'update', [], "rds_migration_update", '41 * * * * *'),
        ];
    }
}
