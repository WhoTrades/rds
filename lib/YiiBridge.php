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

        $application = new \app\components\ExternalApplication(require($config), $debugLogger);

        $application->run();
    }
}
