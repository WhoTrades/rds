<?php
class WebApplication extends CWebApplication
{
    /** @var \ServiceBase_IDebugLogger */
    public $debugLogger;

    public function end($status=0,$exit=true)
    {
        if ($exit) {
            CoreLight::getInstance()->getFatalWatcher()->stop();
        }
        return parent::end();
    }
}