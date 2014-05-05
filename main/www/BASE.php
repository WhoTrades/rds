<?php
define('SCRIPT_START_TIME', microtime(true)); // save script start time

class Config
{
    protected static $instance;

    function __construct($configLocations)
    {
        $path = dirname(__FILE__) . '/../../';
        foreach (array(
                     'config/config.db.php', // vdm: подключил т.к. он используется внутри config.pgq
                     'config/config.servicebase.php',
                     'config/config.services.php',
                     'config.service.php',
                     'config.local.php',
                 ) as $configLocation) {
            if (file_exists($path . $configLocation)) {
                require $path . $configLocation;
            }
        }
        $this->project = 'phplogs';
    }

    public static function createInstance($config = array())
    {
        return self::getInstance($config);
    }

    public static function getInstance($config = array())
    {
        if (null === self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
}

require_once __DIR__ . '/../../init-libraries.php';
require_once __DIR__ . '/../../RequestHandler/Dispatcher.php';

$Core = \CoreLight::getInstance(__DIR__ . "/../../")->init();

$Core->getServiceBaseDebugLogger()->tagPersistently(array('source' => 'www'))->tagPersistently(array('service' => 'yii-whotrades'));
$Core->processRequest('yiiWhotrades\\RequestHandler\\Dispatcher');
