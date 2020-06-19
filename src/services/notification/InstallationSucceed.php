<?php
/**
 * Release request installation succeed notification
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification;

use tuyakhov\notifications\messages\MailMessage;
use yii\helpers\Url;

class InstallationSucceed extends BaseNotification
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var array
     */
    protected $buildIdList;

    /**
     * @param string $projectName
     * @param string $version
     * @param array $buildIdList
     */
    public function __construct(string $projectName, string $version, array $buildIdList)
    {
        $this->projectName = $projectName;
        $this->version = $version;
        $this->buildIdList = $buildIdList;
    }

    public function exportForMail(): MailMessage
    {
        $subject = "[RDS] Succeed installation of {$this->projectName} - {$this->version}";
        $body = "Проект {$this->projectName} был собран и разложен по серверам.<br />";
        foreach ($this->buildIdList as $buildId) {
            $body .= "<a href='" .
                Url::to(['/build/view', 'id' => $buildId], 'https') .
                "'>Подробнее {$this->projectName} v.{$this->version}</a><br />";
        }

        return $this->generateMailMessage($subject, $body);
    }
}