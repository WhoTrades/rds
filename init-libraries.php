<?php
$includePaths = explode(PATH_SEPARATOR, get_include_path());
// vdm: exclude system's pear from include path
if (($idx = array_search('/usr/share/pear', $includePaths)) !== false) {
    unset($includePaths[$idx]);
}
set_include_path(join(PATH_SEPARATOR, array_merge($includePaths, array(
    // local
    __DIR__ . '/',
    __DIR__ . '/misc/cron/',
    __DIR__ . '/protected/modules/Zoho/',
    // an: prod
    __DIR__ . '/lib',
    __DIR__ . '/lib/creole',
    // developer's
    __DIR__ . '/../../lib/',
    __DIR__ . '/../../lib/libcore/',
    __DIR__ . '/../../lib/pear/',
    __DIR__ . '/../../lib/creole',
    __DIR__ . '/protected/modules/Wtflow',
    __DIR__ . '/protected/components',	
))));
require_once(__DIR__ . "/vendor/autoload.php");
require_once('Autoload.php');
