<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
$config = include('main.php');

unset($config['components']['request']);

return $config;