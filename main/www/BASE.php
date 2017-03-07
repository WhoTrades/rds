<?php
define('SCRIPT_START_TIME', microtime(true)); // save script start time

// an: Этот код будет отрабатывать только на dev/tst контурах, и на специальном сервере PROD - nyr-ad1
if (isset($_REQUEST['profile_enable']) && function_exists('tideways_enable')) {
    tideways_enable(TIDEWAYS_FLAGS_NO_SPANS);

    register_shutdown_function(function () {
        $data = tideways_disable();

        $runId = uniqid();
        file_put_contents("/tmp/$runId.profiling.xhprof", serialize($data));

        if (isset($_SERVER['HTTP_HOST']) && false !== strpos($_SERVER['HTTP_HOST'], 'tst.whotrades.net')) {
            $host = "xhprof.tst.whotrades.net";
        } elseif (isset($_SERVER['HTTP_HOST']) && preg_match('~\.\w+.whotrades.net~', $_SERVER['HTTP_HOST'])) {
            $host = "xhprof.dev.whotrades.net";
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
        $this->project = 'rds';
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
$Core->processRequest(YiiWhotrades\RequestHandler\Dispatcher::class);
