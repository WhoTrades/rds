<?php
namespace app\components\Sms;

use \yii\base\Event as EventBase;

class Event extends EventBase
{
    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $message;
}
