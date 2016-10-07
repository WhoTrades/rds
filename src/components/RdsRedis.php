<?php
use RedisSystem\IRedisClient;

class RdsRedis extends yii\base\Object
{
    public $host;
    public $port;
    public $timeout;
    public $password;

    private $redisClient;

    /**
     *
     */
    public function init()
    {
        $redisSystem = new RedisSystem\Factory([
            'host' => $this->host,
            'port' => $this->port,
            'timeout' => $this->timeout,
            'password' => $this->password,
        ], \Yii::$app->graphite->getGraphite());

        $this->redisClient = $redisSystem->getRedisClient();
    }

    /**
     * @return IRedisClient
     */
    public function getRedisClient()
    {
        return $this->redisClient;
    }
}
