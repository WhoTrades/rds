<?php
namespace app\extensions\WebSockets;

use yii\base\Component;
use app\components\View;

class WebSockets extends Component
{
    public $server;
    public $zmqLocations;
    public $maxRetries = 50;
    public $retryDelay = 50;

    /**
     * @var \ServiceBase_WebSocketsManager
     */
    public $webSocketsManager;

    /**
     * Инициализация сервиса вебсокетов
     */
    public function init()
    {
        $this->webSocketsManager = new \ServiceBase_WebSocketsManager(
            $this->zmqLocations,
            \Yii::$app->debugLogger
        );
    }

    /**
     * @param View $view
     */
    public function registerScripts(View $view)
    {
        $view->registerJsFile(__DIR__ . "/assets/autobahn.min.js", ['position' => $view::POS_BEGIN]);
        $view->registerJs($this->getInlineJs(), $view::POS_BEGIN);
    }

    /**
     * @param string $channel
     * @param array $data
     */
    public function send($channel, $data)
    {
        $this->webSocketsManager->sendEvent($channel, null, $data);
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
   'wss://'+document.location.host+'$this->server',
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
