<?php
namespace whotrades\rds\components\Jira;

use whotrades\rds\modules\Wtflow\models\JiraAsyncRpc;

class AsyncRpc extends \yii\base\Object
{
    /**
     * @param string $method
     * @param array $args
     * @return null
     */
    public function __call($method, $args)
    {
        $r = new JiraAsyncRpc();
        $r->jar_method = $method;
        $r->jar_arguments = json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $r->save();

        return null;
    }
}
