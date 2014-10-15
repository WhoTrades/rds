<?php
use \Cronjob\ConfigGenerator;
use \Cronjob\ConfigGenerator\Comment;
use \Cronjob\ConfigGenerator\MultiCronCommand;
use \Cronjob\ConfigGenerator\CronCommand;
use \Cronjob\ConfigGenerator\SimpleCommand;
use \Cronjob\ConfigGenerator\PeriodicCommand;
use \Cronjob\ConfigGenerator\MultiCommandToCron;
use \Cronjob\ConfigGenerator\MultiPeriodicCommand;

class ServiceRdsTestTL1 extends ServiceRdsProdTL1
{
    protected function getAllCommands()
    {
        return array_merge(parent::getAllCommands(), [
            new CronCommand(new PeriodicCommand(Cronjob_Tool_HardMigrationStarter::getToolCommand(['--max-duration=60'], $verbosity=1), $delay=60)),
        ]);
    }

    protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/dev/dev/services/rds/misc/tools',
        ];
    }
}