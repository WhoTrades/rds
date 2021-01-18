<?php
namespace whotrades\rds\extensions\WebSockets;

use yii\base\Component;
use whotrades\rds\components\View;
use ZMQ;
use ZMQContext;
use ZMQSocket;

class WebSockets extends Component
{
    public $server;
    public $zmqLocations;
    public $maxRetries = 50;
    public $retryDelay = 50;

    /** @var ZMQSocket[]*/
    private $sockets;

    private $isSocketConnected = false;

    /**
     * Инициализация сервиса вебсокетов
     */
    public function init()
    {
        foreach ($this->zmqLocations as $location) {
            $context = new ZMQContext();
            $this->sockets[$location] = $context->getSocket(ZMQ::SOCKET_PUSH);
            $this->sockets[$location]->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        }
    }

    /**
     * @param View $view
     */
    public function registerScripts(View $view)
    {
        WebSocketsAsset::register($view);
        $view->registerJs($this->getInlineJs(), $view::POS_BEGIN);
    }

    private function ensureConnected()
    {
        if ($this->isSocketConnected) {
            return;
        }

        foreach ($this->zmqLocations as $location) {
            $this->sockets[$location]->connect($location);
        }

        $this->isSocketConnected = true;
    }

    /**
     * @param string $channel
     * @param array $data
     */
    public function send($channel, $data)
    {
        $this->ensureConnected();
        $package = [
            'channel' => $channel,
            'data' => array(
                'event' => null,
                'data'  => $data,
            ),
            'time' => time(),
        ];

        foreach ($this->sockets as $sockets) {
            $sockets->send(json_encode($package));
        }
    }

    private function getInlineJs()
    {
        return <<<here
var webSocketSession = null;
webSocketSession = {
    subscribes: [],
    session: null,
    subscribe: function(channel, callback){
        if (typeof webSocketSession.subscribes[channel] === "undefined") {
            webSocketSession.subscribes[channel] = [];
        }
        webSocketSession.subscribes[channel].push(callback);
        if (webSocketSession.session) {
            webSocketSession.session.subscribe(channel, callback);
        }
    },
    resubscribe: function(){
        for (channel in webSocketSession.subscribes) {
            for (j in webSocketSession.subscribes[channel]) {
                if (typeof webSocketSession.subscribes[channel][j] == "function") {
                    webSocketSession.session.subscribe(channel, webSocketSession.subscribes[channel][j]);
                }
            }
        }
    }
};

ab.connect(
   'ws://'+document.location.host+'$this->server',
   function (session) {
        webSocketSession.session = session;
        webSocketSession.resubscribe();
   },
   function (code, reason, detail) {

   },
   {
       'maxRetries': $this->maxRetries,
       'retryDelay': $this->retryDelay
   }
);
here;
    }
}
