<?php
class YiiPgq extends PgQ\Cronjob\RequestHandler\Pgq
{
    public function __construct($debugLogger)
    {
        //an: Инициализируем ядро Yii
        YiiBridge::init($debugLogger);

        parent::__construct($debugLogger);
    }
}