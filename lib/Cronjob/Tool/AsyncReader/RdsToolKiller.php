<?php
/**
 * @example dev/services/deploy/misc/tools/runner.php --tool=Deploy_Killer -vv
 */

use RdsSystem\lib\CommandExecutor;
use RdsSystem\lib\CommandExecutorException;

class Cronjob_Tool_AsyncReader_RdsToolKiller extends \RdsSystem\Cron\RabbitDaemon
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $model->readGetProcessGroupInfo(false, function(\RdsSystem\Message\Tool\KillTask $task) use ($model) {
            $commandExecutor = new CommandExecutor($this->debugLogger);



            $task->accepted();
        });

        $this->waitForMessages($model, $cronJob);
    }
}
