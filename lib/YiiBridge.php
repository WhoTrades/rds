<?php

class YiiBridge
{
    public static function init()
    {
        $config = dirname(__FILE__) . '/../protected/config/console.php';
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

        new \app\components\ConsoleApplication(require($config));
    }
}
