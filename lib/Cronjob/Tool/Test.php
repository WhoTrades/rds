<?php
use \Cronjob\ConfigGenerator;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=Test -vv
 */
class Cronjob_Tool_Test extends Cronjob\Tool\ToolBase
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array();
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $rdsSystem = new RdsSystem\Factory($this->debugLogger);
    }
}
