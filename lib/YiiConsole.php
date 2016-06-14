<?php
class YiiConsole extends Cronjob\RequestHandler\Console
{
    /**
     * YiiConsole constructor.
     *
     * @param ServiceBase_IDebugLogger $debugLogger
     */
    public function __construct(\ServiceBase_IDebugLogger $debugLogger)
    {
        //an: Инициализируем ядро Yii
        YiiBridge::init($debugLogger);

        parent::__construct($debugLogger);
    }
}
