<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Maintenance_MasterTool -vv
 */

use RdsSystem\Message;

class Cronjob_Tool_Maintenance_MasterTool extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [
            'server' => [
                'desc' => '',
                'useForBaseName' => true,
                'valueRequired' => true,
            ],
        ] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $server = $cronJob->getOption('server') ?: gethostname();
        $model = $this->getMessagingModel($cronJob);
        $commandExecutor = new \RdsSystem\lib\CommandExecutor($this->debugLogger);

        $model->readToolGetInfoTaskRequest(false, function(RdsSystem\Message\Tool\GetInfoTask $task) use ($server, $model, $commandExecutor) {
            $this->debugLogger->message("Received get process info message");

            try {
                $text = $commandExecutor->executeCommand("ps -Ao pid,command|grep 'sys__key=$task->key'|grep 'sys__package=$task->project-'|grep -v periodic|grep -P '^\\s*\\d+ php'");
            } catch (\RdsSystem\lib\CommandExecutorException $e) {
                if ($e->getCode() == 1) {
                    //an: grep возвращает exit-code=1, если вернулось 0 строк
                    $text = "";
                } else {
                    throw $e;
                }
            }

            $lines = array_filter(explode("\n", str_replace("\r", "", $text)));
            $processList = [];
            foreach ($lines as $line) {
                preg_match('~^\s*(?<pid>\d+)\s*(?<command>.*)$~', $line, $ans);
                $processList[(int)$ans['pid']] = $ans['command'];
            }

            $model->sendToolGetInfoResult(
                new RdsSystem\Message\Tool\GetInfoResult($task->getUniqueTag(), $server, $processList)
            );

            $this->debugLogger->message("Message accepted");
            $task->accepted();
        });

        $model->readToolKillTaskRequest(false, function(RdsSystem\Message\Tool\KillTask $task) use ($server, $model, $commandExecutor) {
            $this->debugLogger->message("Received message");

            try {
                $text = $commandExecutor->executeCommand("ps -Ao pid,command|grep 'sys__key=$task->key'|grep 'sys__package=$task->project-'|grep -v periodic|grep -P '^\\s*\\d+ php'");
            } catch (\RdsSystem\lib\CommandExecutorException $e) {
                if ($e->getCode() == 1) {
                    //an: grep возвращает exit-code=1, если вернулось 0 строк
                    $text = "";
                } else {
                    throw $e;
                }
            }

            $lines = array_filter(explode("\n", str_replace("\r", "", $text)));
            $processList = [];
            foreach ($lines as $line) {
                preg_match('~^\s*(?<pid>\d+)\s*(?<command>.*)$~', $line, $ans);
                $processList[(int)$ans['pid']] = ['command' => $ans['command'], 'killed' => false];
            }

            foreach ($processList as $pid => $data) {
                $this->debugLogger->message("Killing process $pid");
                $processList[$pid]['killed'] = posix_kill($pid, $task->signal);
            }

            $model->sendToolKillResult(
                new RdsSystem\Message\Tool\KillResult($task->getUniqueTag(), $server, $processList)
            );

            $this->debugLogger->message("Message accepted");
            $task->accepted();
        });

        $model->readToolGetToolLogTail(false, function(RdsSystem\Message\Tool\ToolLogTail $task) use ($server, $model, $commandExecutor) {
            $this->debugLogger->message("Received message of tail ");

            $filename = "/var/log/storelog/cronjobs/".$task->tag.".log";

            if (!file_exists($filename)) {
                $this->debugLogger->message("File $filename not found");
                $model->sendToolGetToolLogTailResult(
                    new RdsSystem\Message\Tool\ToolLogTailResult($task->getUniqueTag(), false, $server, "No logs")
                );

                $this->debugLogger->message("Message accepted");
                $task->accepted();
                return;
            }


            $command = "tail -n ".(int)$task->linesCount." ".escapeshellarg($filename);
            try {
                $text = $commandExecutor->executeCommand($command);
            } catch (\RdsSystem\lib\CommandExecutorException $e) {

                $this->debugLogger->error("Error occured during command execution: ".$e->getMessage());
                $model->sendToolGetToolLogTailResult(
                    new RdsSystem\Message\Tool\ToolLogTailResult($task->getUniqueTag(), false, $server, $e->getMessage())
                );

                $this->debugLogger->message("Message accepted");
                $task->accepted();
                return;
            }

            $model->sendToolGetToolLogTailResult(
                new RdsSystem\Message\Tool\ToolLogTailResult($task->getUniqueTag(), true, $server, $text)
            );

            $this->debugLogger->message("Message accepted");
            $task->accepted();
        });

        $this->waitForMessages($model, $cronJob);
    }
}

