<?php
class ServiceRds2ProdTL1 extends ServiceRdsProdTL1
{
    protected function getEnv()
    {
        return [
            'MAILTO=adm+ny_cron@whotrades.org',
            'CRONJOB_TOOLS=/var/www/service-rds2/misc/tools',
        ];
    }
}
