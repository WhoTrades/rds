<?php
final class Whotrades extends yii\base\Object
{
    /** @var \JsonRpcClient */
    protected $rpc;

    private $url;
    private $timeout = 10;
    private $debugLogger;

    public function __construct()
    {
        $this->rpc = new \JsonRpcClient($this->url, \Yii::$app->debugLogger, true, $this->timeout);
    }

    private function reCreateJsonClient()
    {
        $this->rpc = new \JsonRpcClient($this->url, \Yii::$app->debugLogger, true, $this->timeout);
    }

    public function setUrl($url)
    {
        $this->url = $url;
        $this->reCreateJsonClient();
    }

    public function init()
    {

    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        $this->reCreateJsonClient();
    }

    public function __call($method, $params)
    {
        return call_user_func_array(array($this->rpc, $method), $params);
    }
}
