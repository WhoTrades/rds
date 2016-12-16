<?php
use \Cronjob\ConfigGenerator;
use \Cronjob\ConfigGenerator\Comment;
use \Cronjob\ConfigGenerator\MultiCronCommand;
use \Cronjob\ConfigGenerator\CronCommand;
use \Cronjob\ConfigGenerator\SimpleCommand;
use \Cronjob\ConfigGenerator\MultiCommandToCron;

/** @example sphp dev/services/rds/misc/tools/runner.php --tool=CodeGenerate_CronjobGenerator -vv --project=service-rds --env=prod --server=1 */


class ServiceRdsProdTL2
{
    public function getCronConfigRows()
    {
        $allCommands = $this->getAllCommands();

        $allCommands = new MultiCronCommand($allCommands);

        $rows = $allCommands->getCronConfigRows();

        return array_merge($this->getEnv(), $rows);
    }

    protected function getAllCommands()
    {
        return [
            new Comment("Misc"),
            new CronCommand(
                Cronjob_Tool_Maintenance_MasterTool::getToolCommand(['--max-duration=60', '--worker-name=debian'], $verbosity = 1),
                '* * * * * *',
                'rds_master_tool-debian'
            ),
            new CronCommand(
                Cronjob_Tool_Maintenance_MasterTool::getToolCommand(['--max-duration=60', '--worker-name=debian-fast'], $verbosity = 1),
                '* * * * * *',
                'rds_master_tool-debian_fast'
            ),
        ];
    }

    protected function getEnv()
    {
        return [
            'MAILTO=adm+ny_cron@whotrades.org',
            'CRONJOB_TOOLS=/var/www/service-rds/misc/tools',
        ];
    }
}
