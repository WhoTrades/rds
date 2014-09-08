#!/usr/bin/php -qC
<?php
/**
 * Universal runner for cronjob tools
 *
 * @author Dmitry Vorobyev
 * @copyright © 2009—2011 Open Web Technologies, Ltd. (http://openwebtech.ru). All rights reserved.
 * @example misc/tools/periodic.php -vvv --delay=1 misc/tools/runner.php --tool=Sleeper -vvv --time=2
 * @example misc/tools/periodic.php -vvv --delay=1 echo test
 */
$requestHandlerClass = 'Cronjob\RequestHandler\Periodic';
require_once dirname(__FILE__) . '/bootstrap.php';
