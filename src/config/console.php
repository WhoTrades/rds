<?php
use whotrades\RdsSystem\lib\ConsoleErrorHandler;

$config = include('main.php');
$config['controllerMap']['stubs']['class'] = 'bazilio\stubsgenerator\StubsController';
$config['aliases']['@whotrades/rds'] = 'src';

$config['controllerNamespace'] = 'whotrades\\rds\\commands';
unset($config['components']['session']);
unset($config['components']['request']);

$config['components']['errorHandler'] = array(
    'class' => ConsoleErrorHandler::class,
    'discardExistingOutput' => false,
);

$config['components']['urlManager']['hostInfo'] = 'https://rds.myhost.com';

if (file_exists(__DIR__ . "/../../config.local.php")) {
    require(__DIR__ . "/../../config.local.php");
}

return $config;
