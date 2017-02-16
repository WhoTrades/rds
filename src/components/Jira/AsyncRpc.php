<?php
namespace app\components\Jira;

class AsyncRpc extends \yii\base\Object
{
    /** @var \ServiceBase_IDebugLogger */
    private $debugLogger;

    public function __construct(\ServiceBase_IDebugLogger $debugLogger) {
        $this->debugLogger = \Yii::$app->debugLogger;
    }

    public function __call($method, $args)
    {
        $r = new \JiraAsyncRpc();
        $r->jar_method = $method;
        $r->jar_arguments = json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $r->save();

        return null;
    }
}
