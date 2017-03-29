<?php
namespace app\components;

use yii\web\CacheSession;

class Session extends CacheSession
{
    /**
     * @param bool $deleteOldSession
     */
    public function regenerateID($deleteOldSession = null)
    {
        // ignore
    }
}
