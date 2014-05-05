<?php
$dld = 'an';
$this->project = 'comon';
require_once dirname(__FILE__) . '/config/env-dev.php';

$this->ssl['enabled'] = false;
$this->use_double_post_protector = true;

$this->phpLogsSystem['service']['location'] = "http://phplogs.an.whotrades.net/api/";

function vardumpd()
{
    if (!CONFIG_DEBUG_MODE) {
        return;
    }
    CoreLight::getInstance()->getFatalWatcher()->stop();
    var_dump(count($a = func_get_args()) > 1 ? $a : current($a));
    die();
}
