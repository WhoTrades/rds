<?php
$config = include('main.php');

unset($config['components']['request']);
unset($config['components']['errorHandler']);

return $config;
