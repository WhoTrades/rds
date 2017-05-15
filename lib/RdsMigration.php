<?php
class RdsMigration extends Cronjob\RequestHandler\Migration
{
    protected function getMigrationSystemConfig()
    {
        $config = parent::getMigrationSystemConfig();

        $config['components']['db'] = $config['components']['db4'];

        return $config;
    }
}
