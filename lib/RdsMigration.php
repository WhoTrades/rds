<?php
class RdsMigration extends Cronjob\RequestHandler\Migration
{
    public function __construct(\ServiceBase_IDebugLogger $debugLogger)
    {
        $config = dirname(__FILE__) . '/../protected/config/main.php';
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

        include("yii/yii.php");

        if (is_dir(__DIR__ . '/MigrationSystem')) {
            $filename = __DIR__ . '/MigrationSystem/components/ConsoleApplication.php';
        } else {
            $filename = __DIR__ . '/../../../lib/MigrationSystem/components/ConsoleApplication.php';
        }
        
        require_once($filename);
        $application = \Yii::createApplication('\MigrationSystem\components\ConsoleApplication', $config);
        $application->debugLogger = $debugLogger;

        if (is_dir(__DIR__.'/MigrationSystem')) {
            Yii::setPathOfAlias('MigrationSystem', __DIR__.'/MigrationSystem');
        } else {
            Yii::setPathOfAlias('MigrationSystem', __DIR__ . '/../../../lib/MigrationSystem');
        }

        Yii::import('MigrationSystem.components.*');

        parent::__construct($debugLogger);
    }
}

