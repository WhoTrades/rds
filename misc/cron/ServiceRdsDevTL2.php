<?php
class ServiceRdsDevTL2 extends ServiceRdsTestTL2
{
   protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/an/dev/services/rds/misc/tools',
        ];
    }
}