<?php
/**
 * Base notification with generate messages helpers
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use Yii;
use tuyakhov\notifications\NotificationInterface;
use tuyakhov\notifications\NotificationTrait;
use tuyakhov\notifications\messages\MailMessage;
use tuyakhov\notifications\messages\TelegramMessage;

abstract class BaseNotification implements NotificationInterface
{
    use NotificationTrait;

    /**
     * @param string $subject
     * @param string $body
     *
     * @return MailMessage
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function generateMailMessage(string $subject, string $body): MailMessage
    {
        return Yii::createObject(
            [
                'class' => MailMessage::class,
                'subject' => $subject,
                'view' => ['html' => '//notifications/template'],
                'viewData' => [
                    'body' => $body,
                ],
            ]
        );
    }

    /**
     * @param string $subject
     * @param string $body
     *
     * @return TelegramMessage
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function generateTelegramMessage(string $subject, string $body): TelegramMessage
    {
        return Yii::createObject(
            [
                'class' => TelegramMessage::class,
                'subject' => $subject,
                'body' => $body,
            ]
        );
    }
}
