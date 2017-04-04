<?php
namespace app\components;

use \GraphiteSystem\Graphite;

class RdsGraphite extends \yii\base\Object
{
    public $host;
    public $port;
    public $protocol;
    public $env;
    public $prefix;
    public $GUIUrl;

    /** @var Graphite */
    private $graphite;

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->graphite = new Graphite([
            'host'      => $this->host,
            'port'      => $this->port,
            'protocol'  => $this->protocol,
            'env'       => $this->env,
            'prefix'    => $this->prefix,
        ]);
    }

    /** @return Graphite */
    public function getGraphite()
    {
        return $this->graphite;
    }
}
