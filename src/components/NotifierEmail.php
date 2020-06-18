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
}
