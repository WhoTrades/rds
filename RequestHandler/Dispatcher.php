<?php
/**
 * @author Artem Naumenko
 */
namespace YiiWhotrades\RequestHandler;

class Dispatcher extends \ServiceBase\AbstractRequestHandler
{
    protected $config;

    /**
     * @var \PhpLogsSystem\Factory
     */
    protected $factory;

    /**
     * @var \ServiceBase_IDebugLogger
     */
    protected $debugLogger;

    function __construct(\ServiceBase_IDebugLogger $debugLogger)
    {
        $this->debugLogger = $debugLogger;
    }

    function handleRequest()
    {
        $debugLogger = $this->debugLogger;

        /** @var $oper \ServiceBase\Debug\Operation\IOperationReporter */
        $oper = $debugLogger->startOperation('handleRequestBase');
        $debugLogger->debug("process=http_request, method={$_SERVER['REQUEST_METHOD']}, request_uri={$_SERVER['REQUEST_URI']}, query_string={$_SERVER['QUERY_STRING']}");

        $config=dirname(__FILE__).'/../protected/config/main.php';
        defined('YII_DEBUG') or define('YII_DEBUG',true);
        defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

        //\CoreLight::getInstance()->getFatalWatcher()->stop();
        include("yii/yii.php");
        \Yii::$enableIncludePath = false;

        require_once(__DIR__.'/../protected/components/WebApplication.php');
        try {
            $application = \Yii::createApplication('WebApplication', $config);
            $application->debugLogger = $debugLogger;

            if (is_dir(__DIR__.'/../lib/MigrationSystem')) {
                \Yii::setPathOfAlias('MigrationSystem', __DIR__.'/../lib/MigrationSystem');
            } else {
                \Yii::setPathOfAlias('MigrationSystem', __DIR__ . '/../../../lib/MigrationSystem');
            }

            \Yii::import('MigrationSystem.components.*');

            $application->run();
        } catch (\Exception $e) {
            \Yii::app()->handleException($e);
        }

        $oper->success();
        return true;
    }


}
