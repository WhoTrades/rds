<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Maintenance_MasterTool -vv
 */

class Cronjob_Tool_Maintenance_MasterTool extends RdsSystem\Cron\RabbitDaemon
{
    /**
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return [
            'server' => [
                'desc' => '',
                'useForBaseName' => true,
                'valueRequired' => true,
            ],
            'worker-name' => [
                'desc' => 'Name of worker',
                'required' => true,
                'valueRequired' => true,
                'useForBaseName' => true,
            ],
        ] + parent::getCommandLineSpec();
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $server = $cronJob->getOption('server') ?: gethostname();
        $model = $this->getMessagingModel($cronJob);
        $commandExecutor = new \RdsSystem\lib\CommandExecutor();
        $workerName = $cronJob->getOption('worker-name');

        $model->readToolGetInfoTaskRequest($workerName, false, function (RdsSystem\Message\Tool\GetInfoTask $task) use ($server, $model, $commandExecutor, $workerName) {
            Yii::info("Received get process info message");

            try {
                $command = "ps -Ao pid,bsdstart,command|grep 'sys__key=$task->key'|grep -P '\s--sys__package=$task->project-[0-9.]+(\s|$)' | grep -v 'set -o pipefail'";
                $text = $commandExecutor->executeCommand($command);
            } catch (\RdsSystem\lib\CommandExecutorException $e) {
                if ($e->getCode() == 1) {
                    // an: grep возвращает exit-code=1, если вернулось 0 строк
                    $text = "";
                } else {
                    throw $e;
                }
            }

            $lines = array_filter(explode("\n", str_replace("\r", "", $text)));
            $processList = [];
            foreach ($lines as $line) {
                preg_match('~^\s*(?<pid>\d+)\s*(?<time>(?:\d\d:\d\d)|(?:\w{3}[: ]\d\d))\s*(?<command>.*)$~', $line, $ans);
                $processList[(int) $ans['pid']] = $ans;
            }

            $model->sendToolGetInfoResult(
                new RdsSystem\Message\Tool\GetInfoResult($task->getUniqueTag(), $server, $processList)
            );

            Yii::info("Message accepted");
            $task->accepted();
        });

        $model->readToolKillTaskRequest($workerName, false, function (RdsSystem\Message\Tool\KillTask $task) use ($server, $model, $commandExecutor, $workerName) {
            Yii::info("Received message");

            try {
                $text = $commandExecutor->executeCommand("ps -Ao pid,command|grep 'sys__key=$task->key'|grep 'sys__package=$task->project-' | grep -v 'set -o pipefail'");
            } catch (\RdsSystem\lib\CommandExecutorException $e) {
                if ($e->getCode() == 1) {
                    // an: grep возвращает exit-code=1, если вернулось 0 строк
                    $text = "";
                } else {
                    throw $e;
                }
            }

            $lines = array_filter(explode("\n", str_replace("\r", "", $text)));
            $processList = [];
            foreach ($lines as $line) {
                preg_match('~^\s*(?<pid>\d+)\s*(?<command>.*)$~', $line, $ans);
                $processList[(int) $ans['pid']] = [
                    'command' => $ans['command'],
                    'killed' => false,
                ];
            }

            foreach ($processList as $pid => $data) {
                if ($pid != getmypid()) {
                    Yii::info("Killing process $pid");
                    $processList[$pid]['killed'] = posix_kill($pid, $task->signal);
                } else {
                    Yii::error("Attempt to kil myself, skip");
                    $processList[$pid]['killed'] = false;
                }
            }

            $model->sendToolKillResult(
                new RdsSystem\Message\Tool\KillResult($task->getUniqueTag(), $server, $processList)
            );

            Yii::info("Message accepted");
            $task->accepted();
        });

        $model->readToolGetToolLogTail($workerName, false, function (RdsSystem\Message\Tool\ToolLogTail $task) use ($server, $model, $commandExecutor, $workerName) {
            Yii::info("Received message of tail ");

            $text = '';
            $isSuccess = true;

            $daysAgo = 0;
            $linesCount = 0;
            do {
                // dg: Формируем имя файла
                $filename = "/var/log/storelog/cronjobs/$task->tag.log";
                if ($daysAgo > 0) {
                    $filename .= '.' . $daysAgo;
                }

                // dg: Проверяем наличие файла
                if (!file_exists($filename)) {
                    if ($daysAgo === 0) {
                        Yii::info("File $filename not found");
                        $text = "No logs";
                        $isSuccess = false;
                    }
                    break;
                }

                try {
                    $command = "tail -n " . ((int) $task->linesCount - $linesCount) . " " . escapeshellarg($filename);
                } catch (\RdsSystem\lib\CommandExecutorException $e) {
                    Yii::error("Error occurred during command execution: " . $e->getMessage());
                    $text = $e->getMessage();
                    $isSuccess = false;
                    break;
                }

                $textPart = $commandExecutor->executeCommand($command) . PHP_EOL;
                $text = $textPart . $text;
                $linesCount += substr_count($textPart, PHP_EOL);
                $daysAgo++;
            } while ($linesCount < $task->linesCount);

            $model->sendToolGetToolLogTailResult(
                new RdsSystem\Message\Tool\ToolLogTailResult($task->getUniqueTag(), $isSuccess, $server, $text)
            );

            Yii::info("Message accepted");
            $task->accepted();
        });

        $this->waitForMessages($model, $cronJob);
    }
}
