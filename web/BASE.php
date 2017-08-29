<?php
require_once(__DIR__ . "/../../vendor/autoload.php");

$config = dirname(__FILE__) . '/../../protected/config/main.php';
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

$application = new \app\components\WebApplication(require($config));
$application->run();
