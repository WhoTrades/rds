<?php
$config = include('main.php');

unset($config['components']['request']);

return $config;
