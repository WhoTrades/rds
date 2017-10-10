<?php
use whotrades\RdsSystem\lib\ConsoleErrorHandler;

$config = include('main.php');
$config['controllerMap']['stubs']['class'] = 'bazilio\stubsgenerator\StubsController';

$config['bootstrap'][] = 'Whotrades';
$config['controllerNamespace'] = 'app\\commands';
unset($config['components']['session']);
unset($config['components']['request']);

$config['components']['errorHandler'] = array(
    'class' => ConsoleErrorHandler::class,
    'discardExistingOutput' => false,
);

return $config;
