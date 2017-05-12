<?php
/**
 * Runner for SQL migration console tools
 *
 * @author Artem Naumenko
 * @example dev/comon/misc/tools/migration.php migration
 * @example dev/comon/misc/tools/migration.php dbfunctions
 */
$requestHandlerClass = RdsMigration::class;
require_once dirname(__FILE__) . '/bootstrap.php';
