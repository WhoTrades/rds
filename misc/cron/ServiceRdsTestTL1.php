<?php
class ServiceRdsTestTL1 extends ServiceRdsProdTL1
{
   protected function getEnv()
    {
        return [
            'CRONJOB_TOOLS=/home/dev/dev/services/rds/misc/tools',
        ];
    }
}