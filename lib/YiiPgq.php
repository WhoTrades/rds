<?php
use RedisSystem\IRedisClient;

class YiiPgq extends PgQ\Cronjob\RequestHandler\Pgq
{
    /**
     * YiiPgq constructor.
     *
     * @param ServiceBase_IDebugLogger $debugLogger
     */
    public function __construct($debugLogger)
    {
        // an: Инициализируем ядро Yii
        YiiBridge::init($debugLogger);

        parent::__construct($debugLogger);
    }

    /** @return IRedisClient */
    public function getRedisClient()
    {
        return Yii::app()->redis->getRedisClient();
    }
}
