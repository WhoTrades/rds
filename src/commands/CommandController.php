<?php
/**
 * @author Artem Naumenko
 */

namespace app\commands;

class CommandController extends \RdsSystem\commands\CommandController
{
    /**
     * @return array
     */
    public function getCommands()
    {
        return [
            "# Основной обработчик событий",
            $this->createCommand(DeployController::class, 'index', [], "rds_async_reader_deploy"),
        ];
    }
}
