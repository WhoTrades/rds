#!/usr/bin/php -qC
<?php
/**
 * Universal PGQQ event processor
 *
 * @author Dmitry Vorobyev
 * @copyright © 2009—2011 Open Web Technologies, Ltd. (http://openwebtech.ru). All rights reserved.
 * @example process.php --event-processor=VideoEncoding --queue-name=video_encoding --consumer-name=video_encoding_consumer --strategy=simple -v process_queue
 */
$requestHandlerClass = 'YiiPgq';
require_once dirname(__FILE__) . '/../../bootstrap.php';
