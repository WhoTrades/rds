<?php
class ServiceRds2ProdTL2 extends ServiceRdsProdTL2
{
    protected function getEnv()
    {
        return [
            'MAILTO=adm+ny_cron@whotrades.org',
            'CRONJOB_TOOLS=/var/www/service-rds2/misc/tools',
        ];
    }
}
