<?php
define('SCRIPT_START_TIME', microtime(true)); // save script start time
//an: Этот код будет отрабатывать только на dev/tst контурах, так как на проде не стоит xhprof
if (isset($_REQUEST['profile_xhprof']) && function_exists('xhprof_enable')){
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

    function on_shutdown_debug(){
        $xhprof_data = xhprof_disable();

        $XHPROF_ROOT = "/var/www/xhprof/";
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

        $xhprof_runs = new XHProfRuns_Default();
        $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");

        if (isset($_SERVER['HTTP_HOST']) && false !== strpos($_SERVER['HTTP_HOST'], 'tst.whotrades.net')) {
            $host = "xhprof.tst.whotrades.net";
        } else {
            $host = "xhprof.dev.whotrades.net";
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
                      div.innerHTML = '<a target=\"_blank\" href=\"http://$host/xhprof_html/index.php?run={$run_id}&source=xhprof_testing\">Profiler</a><br />';
                      div.innerHTML += '<a target=\"_blank\" href=\"http://$host/xhprof_html/callgraph.php?run={$run_id}&source=xhprof_testing\">Call graph</a>';
                      document.body.appendChild(div);
                  </script>";
    }

    register_shutdown_function('on_shutdown_debug');
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
$Core->processRequest('yiiWhotrades\\RequestHandler\\Dispatcher');
