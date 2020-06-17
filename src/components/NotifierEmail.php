<?php
namespace whotrades\rds\components;

use Yii;
use yii\helpers\Url;

class NotifierEmail extends \yii\base\BaseObject
{
    public $releaseRequestedEmail;
    public $releaseRejectedEmail;
    public $releaseRequestEmail;
    public $mergeConflictEmail;
    public $releaseReleasedEmail;

    /**
     * @return yii\mail\MessageInterface
     */
    protected function getMailer($content)
    {
        $mailer = Yii::$app->mailer->compose('base', ['content' => $content]);

        return $mailer;
    }

    /**
     * @param string $title
     * @param string $text
     *
     * @return bool
     */
    public function sendReleaseRejectCustomNotification($title, $text)
    {
        $mail = $this->getMailer($text);
        $mail->setFrom([$this->releaseRequestedEmail => 'releases']);
        $mail->setTo($this->releaseRequestedEmail);
        $mail->setSubject("[RDS] $title");

        return $mail->send();
    }

    /**
     * Дает взможность проксировать и расширять логику обработки уведомлений о падение сборки
     *
     * @param $projectName
     * @param $title
     * @param $text
     *
     * @return bool
     */
    public function sendReleaseRequestFailedNotification($projectName, $title, $text)
    {
        return $this->sendReleaseRejectCustomNotification($title, $text);
    }

    /**
     * @param string $projectName
     * @param string $version
     * @param array $buildIdList
     */
    public function sendReleaseRequestDeployNotification($projectName, $version, array $buildIdList)
    {
        $title = "Success installed $projectName v.$version";
        $text = "Проект $projectName был собран и разложен по серверам.<br />";
        foreach ($buildIdList as $buildId) {
            $text .= "<a href='" .
                Url::to(['build/view', 'id' => $buildId], 'https') .
                "'>Подробнее {$projectName} v.{$version}</a><br />";
        }

        $this->sendReleaseRejectCustomNotification($title, $text);

        foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
            if (!$phone) {
                continue;
            }
            Yii::$app->smsSender->sendSms($phone, $title);
        }
    }

    /**
     * @param string $user
     * @param string $projectName
     * @param string $comment
     *
     * @return bool
     */
    public function sendRdsReleaseRejectNotification($user, $projectName, $comment)
    {
        $mail = $this->getMailer("Запрет на релиз $projectName<br />Автор: {$user}<br />Комментарий: {$comment}");
        $mail->setFrom([$this->releaseRejectedEmail => 'releases']);
        $mail->setTo($this->releaseRejectedEmail);
        $mail->setSubject("[RDS] Запрет релиза $projectName");

        return $mail->send();
    }

    /**
     * @param string $userName
     * @param string $projectName
     * @param string $comment
     *
     * @return bool
     */
    public function sendRdsReleaseRequestNotification($userName, $projectName, $comment)
    {
        $mail = $this->getMailer("Запрос релиза для $projectName<br />Автор: {$userName}<br />Комментарий: {$comment}");
        $mail->setFrom([$this->releaseRequestedEmail => 'releases']);
        $mail->setTo($this->releaseRequestedEmail);
        $mail->setSubject("[RDS] Запрос релиза для $projectName");

        return $mail->send();
    }

    /**
     * @param string $projectName
     * @param string $newVersion
     *
     * @return bool
     */
    public function sendReleaseReleased($projectName, $newVersion)
    {
        $mail = $this->getMailer("Состоялся релиз $projectName v. $newVersion");
        $mail->setFrom([$this->releaseReleasedEmail => 'releases']);
        $mail->setTo($this->releaseReleasedEmail);
        $mail->setSubject("[RDS] Состоялся релиз $projectName v. $newVersion");

        return $mail->send();
    }
}
