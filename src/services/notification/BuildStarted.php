<?php
/**
 * Release request build started notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;

class BuildStarted extends BaseNotification
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $userName;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @param string $projectName
     * @param string $userName
     * @param string $comment
     */
    public function __construct(string $projectName, string $userName, string $comment)
    {
        $this->projectName = $projectName;
        $this->userName = $userName;
        $this->comment = $comment;
    }

    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] Started build of $this->projectName";
        $body = "Started build of {$this->projectName}<br />Author: {$this->userName}<br />Comment: {$this->comment}";

        return $this->generateMailMessage($subject, $body);
    }
}