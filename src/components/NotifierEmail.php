<?php
class NotifierEmail extends CComponent
{
    public $releaseRequestedEmail;
    public $releaseRejectedEmail;
    public $releaseRequestEmail;
    public $mergeConflictEmail;
    public $releaseReleasedEmail;

    public function init()
    {

    }

    /**
     * @param string $title
     * @param string $text
     *
     * @return bool
     * @throws CException
     * @throws phpmailerException
     */
    public function sendReleaseRejectCustomNotification($title, $text)
    {
        $mail = new YiiMailer();
        $mail->setBody($text);
        $mail->setLayout('mail');
        $mail->setFrom($this->releaseRequestedEmail, 'releases');
        $mail->setTo($this->releaseRequestedEmail);
        $mail->setSubject("[RDS] $title");

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
     * @throws phpmailerException
     */
    public function sendRdsConflictNotification($developerEmail, $developerGroupEmail, $ticket, $targetBranch, $text)
    {
        $mail = new YiiMailer();
        $mail->setBody($text);
        $mail->setLayout('mail');
        $mail->setFrom($this->mergeConflictEmail, 'releases');
        $mail->setTo($developerEmail);
        $mail->addCC($developerGroupEmail);
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
     * @throws phpmailerException
     */
    public function sendRdsReleaseRejectNotification($user, $projectName, $comment)
    {
        $mail = new YiiMailer();
        $mail->setBody("Запрет на релиз $projectName<br />Автор: {$user}<br />Комментарий: {$comment}");
        $mail->setLayout('mail');
        $mail->setFrom($this->releaseRejectedEmail, 'releases');
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
     * @throws phpmailerException
     */
    public function sendRdsReleaseRequestNotification($userName, $projectName, $comment)
    {
        $mail = new YiiMailer();
        $mail->setBody("Запрос релиза для $projectName<br />Автор: {$userName}<br />Комментарий: {$comment}");
        $mail->setLayout('mail');
        $mail->setFrom($this->releaseRequestedEmail, 'releases');
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
     * @throws phpmailerException
     */
    public function sendReleaseReleased($projectName, $newVersion)
    {
        $mail = new YiiMailer();
        $mail->setBody("Состоялся релиз $projectName v. $newVersion");
        $mail->setLayout('mail');
        $mail->setFrom($this->releaseReleasedEmail, 'releases');
        $mail->setTo($this->releaseReleasedEmail);
        $mail->setSubject("[RDS] Состоялся релиз $projectName v. $newVersion");

        return $mail->send();
    }
}
