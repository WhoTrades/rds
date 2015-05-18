<?php
class ServiceRdsDevTL1 extends ServiceRdsTestTL1
{
   protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/dev/dev/services/rds/misc/tools',
        ];
    }
}