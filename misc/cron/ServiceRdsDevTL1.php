<?php
class ServiceRdsDevTL1 extends ServiceRdsProdTL1
{
   protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/an/dev/services/rds/misc/tools',
        ];
    }
}