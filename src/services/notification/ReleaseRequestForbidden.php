<?php
/**
 * Prohibition of release request was set notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;

class ReleaseRequestForbidden extends BaseNotification
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @param string $projectName
     * @param string $user
     * @param string $comment
     */
    public function __construct(string $projectName, string $user, string $comment)
    {
        $this->projectName = $projectName;
        $this->user = $user;
        $this->comment = $comment;
    }

    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] Запрет релиза {$this->projectName}";
        $body = "Запрет на релиз {$this->projectName}<br />Автор: {$this->user}<br />Комментарий: {$this->comment}";

        return $this->generateMailMessage($subject, $body);
    }
}