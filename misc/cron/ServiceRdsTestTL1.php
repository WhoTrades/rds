<?php
use \Cronjob\ConfigGenerator;
use \Cronjob\ConfigGenerator\Comment;
use \Cronjob\ConfigGenerator\MultiCronCommand;
use \Cronjob\ConfigGenerator\CronCommand;
use \Cronjob\ConfigGenerator\SimpleCommand;
use \Cronjob\ConfigGenerator\MultiCommandToCron;

class ServiceRdsTestTL1 extends ServiceRdsProdTL1
{
    protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/dev/dev/services/rds/misc/tools',
        ];
    }
}