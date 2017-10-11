<?php
namespace whotrades\rds\components\Sms;

use \yii\base\Component;

class Sender extends Component
{
    const EVENT_SEND_SMS = 'send_sms';

    /**
     * @param string $phone
     * @param string $message
     */
    public function sendSms($phone, $message)
    {
        $event = new Event();
        $event->phone = $phone;
        $event->message = $message;

        $this->trigger(self::EVENT_SEND_SMS, $event);
    }
}
