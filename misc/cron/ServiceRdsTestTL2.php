<?php
class ServiceRdsTestTL2 extends ServiceRdsProdTL2
{
   protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/dev/dev/services/rds/misc/tools',
        ];
    }
}