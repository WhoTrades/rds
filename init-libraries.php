<?php
$includePaths = explode(PATH_SEPARATOR, get_include_path());
// vdm: exclude system's pear from include path
if (($idx = array_search('/usr/share/pear', $includePaths)) !== false) {
	unset($includePaths[$idx]);
}
set_include_path(join(PATH_SEPARATOR, array_merge($includePaths, array(
	// local
	dirname(__FILE__) . '/',
	dirname(__FILE__) . '/misc/cron/',
	//an: prod
	dirname(__FILE__) . '/lib',
	// developer's
	dirname(__FILE__) . '/../../lib/',
	dirname(__FILE__) . '/../../lib/libcore/',
	dirname(__FILE__) . '/../../lib/pear/',
	dirname(__FILE__) . '/../../lib/creole',
))));

require_once 'Autoload.php';

function __autoload__foreign($className)
{
    $locations = array();

    if ($relativeLocation = __autoload__foreign_relativeLocation($className)) {
        $locations[] = dirname(__FILE__) . '/' . $relativeLocation; // vdm: in nested lib directory
        $locations[] = dirname(__FILE__) . '/../../../lib/' . $relativeLocation; // vdm: in dev lib directory
    }
    return $locations;
}

function __autoload__foreign_relativeLocation($className)
{
    if ($className === 'Creole') {
        return 'creole/Creole.php';
    }
    if ($className === 'Connection') {
        return 'creole/Connection.php';
    }

    if ($className === 'CreoleTypes') {
        return 'creole/CreoleTypes.php';
    }
    if ($className === 'PgSQLTypes') {
        return 'creole/drivers/pgsql/PgSQLTypes.php';
    }
    if ($className === 'SQLException') {
        return 'creole/SQLException.php';
    }
    return null;
}
Autoload::registerAutoload('__autoload__foreign');


