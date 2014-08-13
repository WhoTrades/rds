<?php
class Config
{
    protected static $instance;

    function __construct($configLocations)
    {
        $path = __DIR__.'/';

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

        $this->project = 'rds';
        $this->projectLocation = __DIR__;
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

require_once __DIR__ . '/init-libraries.php';

//an: Эта конструкция нужна для корректной работы кода на деве
set_include_path(get_include_path().PATH_SEPARATOR.__DIR__.'/../../');

// change the following paths if necessary
$yiic='lib/MigrationSystem/yiic.php';
$config=dirname(__FILE__).'/config/console.php';

require_once($yiic);
