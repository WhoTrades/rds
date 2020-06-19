<?php
/**
 * Release request installation cancelled notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;
use yii\helpers\Url;

class InstallationCanceled extends BaseNotification
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var int
     */
    protected $buildId;

    /**
     * @param string $projectName
     * @param int $buildId
     */
    public function __construct(string $projectName, int $buildId)
    {
        $this->projectName = $projectName;
        $this->buildId = $buildId;
    }

    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] Cancelled installation of $this->projectName";
        $body = "Сборка $this->projectName отменена. <a href='" .
            Url::to(['/build/view', 'id' => $this->buildId], 'https') .
            "'>Подробнее</a>";

        return $this->generateMailMessage($subject, $body);
    }
}