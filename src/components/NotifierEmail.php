<?php
class NotifierEmail extends yii\base\Object
{
    public $releaseRequestedEmail;
    public $releaseRejectedEmail;
    public $releaseRequestEmail;
    public $mergeConflictEmail;
    public $releaseReleasedEmail;

    /**
     * @return yii\mail\MessageInterface
     */
    private function getMailer($content)
    {
        $mailer = Yii::$app->mailer->compose('base', ['content' => $content]);

        return $mailer;
    }
    /**
     * @param string $title
     * @param string $text
     *
     * @return bool
     * @throws CException
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
        $mail->setCC([$developerGroupEmail, "anaumenko@corp.finam.ru"]);
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
     * @throws CException
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
     * @throws CException
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
     * @throws CException
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
     * @throws CException
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
