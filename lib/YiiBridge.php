<?php
class YiiBridge
{
    public static function init($debugLogger)
    {
        static $inited = false;
        if ($inited) {
            return;
        }

        $config = dirname(__FILE__) . '/../protected/config/main.php';
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

        require_once(__DIR__ . '/../protected/components/ExternalApplication.php');
        $application = new \ExternalApplication(require($config));
        $application->debugLogger = $debugLogger;

        $application->run();
    }
}
