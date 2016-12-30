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


        $mail = new YiiMailer();
        $mail->setBody($text);
        $mail->setLayout('mail');
        $mail->setFrom($this->mergeConflictEmail, 'releases');
        $mail->setTo($developerEmail);
        $mail->addCC($developerGroupEmail);
        $mail->addCC("anaumenko@corp.finam.ru");
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

    /**
     * @param string $receiver
     * @param string $tagTo
     * @param array $errors
     * @return bool
     * @throws CException
     * @throws phpmailerException
     */
    public function sentNewSentryErrors($receiver, $tagTo, $errors)
    {
        $mail = new YiiMailer();
        $mail->setLayout('mail');
        $mail->setFrom('report@whotrades.org', 'releases');
        $mail->setTo($receiver);
        $mail->setSubject("[RDS] Новые ошибки после релиза $tagTo");

        $text = "<h1>Новые ошибки в релизе $tagTo:</h1>\n";
        $summaryUsers = 0;
        foreach ($errors as $error) {
            $text .= "<a href='{$error['permalink']}'>{$error['title']}</a><br />\n";
            $text .= "Всего ошибок: <b>{$error['count']}</b>, Пользователей затронуто: <b>{$error['userCount']}</b><br />\n";
            $summaryUsers += $error['userCount'];
            $text .= "<b>{$error['culprit']}</b><br />\n";
            $text .= "<span style='color: gray'>{$error['metadata']['value']}</span><br /><br />\n\n";
        }

        // an: Если ошибок много - добавляем в копию tml@whotrades.org
        if ($summaryUsers > 10) {
            $mail->setSubject("Ошибки в $tagTo, затронуто пользователей: $summaryUsers");
            $mail->setCc('tml@whotrades.org');
            $text = "+tml@whotrades.org<br /><br />\n$text";
        }

        $mail->setBody($text);

        return $mail->send();
    }
}
