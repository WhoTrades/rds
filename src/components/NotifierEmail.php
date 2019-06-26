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
     * @param string $developerEmail
     * @param string $developerGroupEmail
     * @param array  $ticketList
     *
     * @return bool
     */
    public function sendRdsDeveloperFeatureLimitExceeded($developerEmail, $developerGroupEmail, array $ticketList)
    {
        $ticketListString = implode(", ", $ticketList);

        $text = <<<here
Разработчик $developerEmail достиг лимита по количеству открытых задач.<br />
Открытые задачи: $ticketListString<br /><br /

Это письмо означает, что в процессе разработки существуют проблемы: задачи делаются, но не закрываются.<br />
Нужно проанализировать на каком этапе происходит заминка и убрать её, либо перестать делать задачи, которые <br />
останавливаются данной заминкой (например, если проблема в скорости тестирования - делаем задачи, не требующие тестирования)
here;


        $mail = $this->getMailer($text);
        $mail->setFrom([$this->mergeConflictEmail => 'releases']);
        $mail->setTo($developerEmail);
        $mail->setCC($developerGroupEmail);
        $mail->setSubject("[RDS] Достигнут лимит максимального количества открытых задач на разработчика");

        return $mail->send();
    }

    /**
     * @param string $developerEmail
     * @param string $developerGroupEmail
     * @param string $ticket
     * @param string $targetBranch
     * @param string $text
     *
     * @return bool
     */
    public function sendRdsConflictNotification($developerEmail, $developerGroupEmail, $ticket, $targetBranch, $text)
    {
        $mail = $this->getMailer($text);
        $mail->setFrom([$this->mergeConflictEmail => 'releases']);
        $mail->setTo($developerEmail);
        $mail->setCC($developerGroupEmail);
        $mail->setSubject("[RDS] Merge conflict at $ticket, branch=$targetBranch, developer=$developerEmail");

        return $mail->send();
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

    /**
     * @param string $receiver
     * @param string $tagTo
     * @param array $errors
     * @return bool
     */
    public function sentNewSentryErrors($receiver, $tagTo, $errors)
    {
        $text = "<h1>Новые ошибки в релизе $tagTo:</h1>\n";
        $summaryUsers = 0;
        foreach ($errors as $error) {
            $text .= "<a href='{$error['permalink']}'>{$error['title']}</a><br />\n";
            $text .= "Всего ошибок: <b>{$error['count']}</b>, Пользователей затронуто: <b>{$error['userCount']}</b><br />\n";
            $summaryUsers += $error['userCount'];
            $text .= "<b>{$error['culprit']}</b><br />\n";
            $text .= "<span style='color: gray'>{$error['metadata']['value']}</span><br /><br />\n\n";
        }

        $mail = $this->getMailer($text);
        $mail->setFrom(['report@whotrades.org' => 'releases']);
        $mail->setTo($receiver);
        $mail->setSubject("[RDS] Новые ошибки после релиза $tagTo");

        // an: Если ошибок много - добавляем в копию tml@whotrades.org
        if ($summaryUsers > 10) {
            $mail->setSubject("Ошибки в $tagTo, затронуто пользователей: $summaryUsers");
            $mail->setCc('tml@whotrades.org');
        }

        return $mail->send();
    }
}
