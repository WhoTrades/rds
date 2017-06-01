<?php
define('SCRIPT_START_TIME', microtime(true)); // save script start time


// an: Этот код будет отрабатывать только на dev/tst контурах, и на специальном сервере PROD - nyr-ad1
if (isset($_REQUEST['profile_enable']) && function_exists('tideways_enable')) {
    tideways_enable(TIDEWAYS_FLAGS_NO_SPANS);

    register_shutdown_function(function () {
        $data = tideways_disable();

        $runId = uniqid();
        file_put_contents("/tmp/$runId.profiling.xhprof", serialize($data));

        if (preg_match('~\w+\.(\w+\.whotrades.net)~', $_SERVER['HTTP_HOST'], $ans)) {
            $host = "xhprof." . $ans[1];
        } else {
            $host = "xhprof.whotrades.net";
        }

        echo "<script>
                  var div = document.createElement('div');
                  div.style.position = 'fixed';
                  div.style.top = '50px';
                  div.style.left = '10px';
                  div.style.width = '100px';
                  div.style.backgroundColor = '#eee';
                  div.style.border = '2px solid #aaa';
                  div.style.padding = '10px';
                  div.style.overflow = 'hidden';
                  div.innerHTML = '<a target=\"_blank\" href=\"http://$host/xhprof_html/index.php?run={$runId}&source=profiling\">Profiler</a><br />';
                  div.innerHTML += '<a target=\"_blank\" href=\"http://$host/xhprof_html/callgraph.php?run={$runId}&source=profiling\">Call graph</a>';
                  document.body.appendChild(div);
              </script>";
    });
}

/**
 * @property string DSN_DB4
 */
class Config
{
    protected static $instance;

    /**
     * Config constructor.
     */
    private function __construct()
    {
        $path = dirname(__FILE__) . '/../../';
        foreach (array('config.service.php', 'config.local.php') as $configLocation) {
            if (file_exists($path . $configLocation)) {
                require $path . $configLocation;
            }
        }
        $this->project = 'rds';
    }

    /**
     * @param array $config
     * @return Config
     */
    public static function getInstance($config = array())
    {
        if (null === self::$instance) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }
}

require_once __DIR__ . '/../../init-libraries.php';

$syslogWriter = new ServiceBase\Debug\SyslogWriter(ServiceBase\Debug\Syslog::getInstance());
$debugLogger = new ServiceBase\Debug\Logger\LoggerErrorLog(3, $syslogWriter, \Cronjob\Factory::getConsoleOutput());

$config = dirname(__FILE__) . '/../../protected/config/main.php';
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
$application = new \app\components\WebApplication(require($config), $debugLogger);

if (is_dir(__DIR__ . '/../lib/MigrationSystem')) {
    \Yii::setAlias('@MigrationSystem', __DIR__ . '/../lib/MigrationSystem');
} else {
    \Yii::setAlias('@MigrationSystem', __DIR__ . '/../../../lib/MigrationSystem');
}

$application->run();
