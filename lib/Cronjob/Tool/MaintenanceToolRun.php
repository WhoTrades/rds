<?php
use RdsSystem\Message;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=MaintenanceToolRun -vv --tool-name=InstrumentsUpdateTickers
 */
class Cronjob_Tool_MaintenanceToolRun extends RdsSystem\Cron\RabbitDaemon
{
    public static function getCommandLineSpec()
    {
        return [
            'tool-name' => [
                'desc' => 'mt_name of tool to run',
                'required' => true,
                'valueRequired' => true,
            ],
        ] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $tool = MaintenanceTool::model()->findByAttributes(['mt_name' => $cronJob->getOption('tool-name')]);
        if (!$tool) {
            $this->debugLogger->error("Tool not found");
            return 1;
        }

        $mtr = $tool->start("cron runner", false);

        if ($mtr->errors) {
            $this->debugLogger->error("Can't start tool: ".json_encode($mtr->errors));

            return 2;
        }

        $this->debugLogger->message("Tool started successfully");
    }
}
