<?php
/**
 * Writes to log errors of sending
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\events;

use samdark\log\PsrMessage;
use Yii;
use tuyakhov\notifications\events\NotificationEvent;

class NotificationEventHandler
{
    /**
     * @param NotificationEvent $event
     */
    public function afterSend(NotificationEvent $event)
    {
        if ($event->response instanceof \Throwable) {
            Yii::error(new PsrMessage($event->response->getMessage(), ['exception' => $event->response]));
        }
    }
}