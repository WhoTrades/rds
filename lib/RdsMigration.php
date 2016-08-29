<?php
class RdsMigration extends Cronjob\RequestHandler\Migration
{
    public function __construct(\ServiceBase_IDebugLogger $debugLogger)
    {
        $config = dirname(__FILE__) . '/../protected/config/main.php';
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

        $application = \Yii::createApplication('\MigrationSystem\components\ConsoleApplication', $config);
        $application->debugLogger = $debugLogger;

        parent::__construct($debugLogger);
    }
}

