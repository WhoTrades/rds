<?php
/**
 * Release request installation failed notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;
use yii\helpers\Url;

class InstallationFailed extends BaseNotification
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
     * @var int
     */
    protected $failCount;

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     */
    public function __construct(string $projectName, int $buildId, int $failCount)
    {
        $this->projectName = $projectName;
        $this->buildId = $buildId;
        $this->failCount = $failCount;
    }

    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] Failed to install $this->projectName {$this->failCount} times";
        $body = "Проект $this->projectName не удалось разложить по серверам. <a href='" .
            Url::to(['/build/view', 'id' => $this->buildId], 'https') .
            "'>Подробнее</a>";

        return $this->generateMailMessage($subject, $body);
    }
}