<?php
use RdsSystem\lib\ConsoleErrorHandler;

$config = include('main.php');

unset($config['components']['request']);
$config['components']['errorHandler'] = array(
    'class' => ConsoleErrorHandler::class,
    'discardExistingOutput' => false,
);

return $config;
