<?php
/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */

use RdsSystem\Message;
use RdsSystem\lib\CommandExecutor;

class Cronjob_Tool_Test extends RdsSystem\Cron\RabbitDaemon
{
    const PACKAGES_TIMEOUT = 30;

    public static function getCommandLineSpec()
    {
        return [] + parent::getCommandLineSpec();
    }

    public function run(\Cronjob\ICronjob $cronJob)
    {
        $fp = stream_socket_client("udp://localhost:51411", $errno, $errstr);

        if ($fp)
        {
            fwrite($fp, "TEST 1 TEST 2 TEST 3");
            $buf = fgets($fp);
            var_dump($buf);
            fclose($fp);
        }
    }
}
