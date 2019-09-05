<?php
/**
 * @author Artem Naumenko
 */

namespace whotrades\rds\commands;

class CommandController extends \whotrades\RdsSystem\commands\CommandController
{
    /**
     * @return array
     */
    public function getCommands()
    {
        return [
            "# Основной обработчик событий",
            $this->createCommand(DeployController::class, 'index', [], "rds_async_reader_deploy"),
            $this->createCommand(RemovePackagesController::class, 'index', [], "rds_async_reader_remove_packages", '1 */5 * * * *'),
        ];
    }
}
