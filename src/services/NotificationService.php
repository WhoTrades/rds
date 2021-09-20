<?php
/**
 * Service manages sending outer notifications
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services;

use samdark\log\PsrMessage;
use tuyakhov\notifications\NotifiableInterface;
use tuyakhov\notifications\NotificationInterface;
use Yii;
use yii\base\BaseObject;
use tuyakhov\notifications\Notifier;
use whotrades\rds\services\notification;
use whotrades\rds\services\notification\recipient;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;

class NotificationService extends BaseObject implements NotificationServiceInterface
{
    public $releaseRequestEmail;
    public $releaseRequestForbiddenEmail;
    public $usingSucceedEmail;

    /**
     * @var Notifier
     */
    protected $notifier;

    /**
     * @param Notifier $notifierBase
     * @param null $config
     */
    public function __construct(Notifier $notifierBase, $config = null)
    {
        $this->notifier = $notifierBase;

        $config = $config ?? [];
        parent::__construct($config);
    }

    /**
     * {@inheritDoc}
     */
    public function sendBuildStarted(string $projectName, string $userName, string $comment): void
    {
        try {
            $this->notifier->send(
                $this->getBuildStartedRecipientList($projectName, $userName, $comment),
                $this->getBuildStartedNotification($projectName, $userName, $comment)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendBuildFailed(string $projectName, int $buildId, int $failCount): void
    {
        try {
            $this->notifier->send(
                $this->getBuildFailedRecipientList($projectName, $buildId, $failCount),
                $this->getBuildFailedNotification($projectName, $buildId, $failCount)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendInstallationSucceed(string $projectName, string $version, array $buildIdList): void
    {
        try {
            $this->notifier->send(
                $this->getInstallationSucceedRecipientList($projectName, $version, $buildIdList),
                $this->getInstallationSucceedNotification($projectName, $version, $buildIdList)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendInstallationCanceled(string $projectName, int $buildId): void
    {
        try {
            $this->notifier->send(
                $this->getInstallationCanceledRecipientList($projectName, $buildId),
                $this->getInstallationCanceledNotification($projectName, $buildId)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendInstallationFailed(string $projectName, int $buildId, int $failCount): void
    {
        try {
            $this->notifier->send(
                $this->getInstallationFailedRecipientList($projectName, $buildId, $failCount),
                $this->getInstallationFailedNotification($projectName, $buildId, $failCount)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendUsingSucceed(Project $project, ReleaseRequest $releaseRequestNew, ReleaseRequest $releaseRequestOld, string $initiatorUserName): void
    {
        try {
            $this->notifier->send(
                $this->getUsingSucceedRecipientList($project, $releaseRequestNew, $releaseRequestOld),
                $this->getUsingSucceedNotification($project, $releaseRequestNew, $releaseRequestOld, $initiatorUserName)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendReleaseRequestForbidden(string $projectName, string $user, string $comment): void
    {
        try {
            $this->notifier->send(
                $this->getReleaseRequestForbiddenRecipientList($projectName, $user, $comment),
                $this->getReleaseRequestForbiddenNotification($projectName, $user, $comment)
            );
        } catch (\Throwable $e) {
            // Notifications errors shouldn't affect main program flow
            Yii::error(new PsrMessage($e->getMessage(), ['exception' => $e]));
        }
    }

    /**
     * @param string $projectName
     * @param string $userName
     * @param string $comment
     *
     * @return NotifiableInterface[]
     */
    protected function getBuildStartedRecipientList(string $projectName, string $userName, string $comment): array
    {
        return [new recipient\Email($this->releaseRequestEmail)];
    }

    /**
     * @param string $projectName
     * @param string $userName
     * @param string $comment
     *
     * @return NotificationInterface
     */
    protected function getBuildStartedNotification(string $projectName, string $userName, string $comment): NotificationInterface
    {
        return new notification\BuildStarted($projectName, $userName, $comment);
    }

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     *
     * @return NotifiableInterface[]
     */
    protected function getBuildFailedRecipientList(string $projectName, int $buildId, int $failCount): array
    {
        return [new recipient\Email($this->releaseRequestEmail)];
    }

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     *
     * @return NotificationInterface
     */
    protected function getBuildFailedNotification(string $projectName, int $buildId, int $failCount): NotificationInterface
    {
        return new notification\BuildFailed($projectName, $buildId, $failCount);
    }

    /**
     * @param string $projectName
     * @param string $version
     * @param array $buildIdList
     *
     * @return recipient\Email[]
     */
    protected function getInstallationSucceedRecipientList(string $projectName, string $version, array $buildIdList): array
    {
        return [new recipient\Email($this->releaseRequestEmail)];
    }

    /**
     * @param string $projectName
     * @param string $version
     * @param array $buildIdList
     *
     * @return NotificationInterface
     */
    protected function getInstallationSucceedNotification(string $projectName, string $version, array $buildIdList): NotificationInterface
    {
        return new notification\InstallationSucceed($projectName, $version, $buildIdList);
    }

    /**
     * @param string $projectName
     * @param int $buildId
     *
     * @return recipient\Email[]
     */
    protected function getInstallationCanceledRecipientList(string $projectName, int $buildId): array
    {
        return [new recipient\Email($this->releaseRequestEmail)];
    }

    /**
     * @param string $projectName
     * @param int $buildId
     *
     * @return NotificationInterface
     */
    protected function getInstallationCanceledNotification(string $projectName, int $buildId): NotificationInterface
    {
        return new notification\InstallationCanceled($projectName, $buildId);
    }

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     *
     * @return recipient\Email[]
     */
    protected function getInstallationFailedRecipientList(string $projectName, int $buildId, int $failCount): array
    {
        return [new recipient\Email($this->releaseRequestEmail)];
    }

    /**
     * @param string $projectName
     * @param int $buildId
     * @param int $failCount
     *
     * @return NotificationInterface
     */
    protected function getInstallationFailedNotification(string $projectName, int $buildId, int $failCount): NotificationInterface
    {
        return new notification\InstallationFailed($projectName, $buildId, $failCount);
    }

    /**
     * @param Project $project
     * @param ReleaseRequest $releaseRequestNew
     * @param ReleaseRequest $releaseRequestOld
     *
     * @return recipient\Email[]
     */
    protected function getUsingSucceedRecipientList(Project $project, ReleaseRequest $releaseRequestNew, ReleaseRequest $releaseRequestOld): array
    {
        $recipientList = [new recipient\Email($this->usingSucceedEmail)];
        if ($project->project_notification_email) {
            $recipientList[] = new recipient\Email($project->project_notification_email);
        }

        return $recipientList;
    }

    /**
     * @param Project $project
     * @param ReleaseRequest $releaseRequestNew
     * @param ReleaseRequest $releaseRequestOld
     * @param string $initiatorUserName
     *
     * @return NotificationInterface
     */
    protected function getUsingSucceedNotification(
        Project $project,
        ReleaseRequest $releaseRequestNew,
        ReleaseRequest $releaseRequestOld,
        string $initiatorUserName
    ): NotificationInterface {
        $notification = new notification\UsingSucceed($project, $releaseRequestNew, $releaseRequestOld, [], $initiatorUserName);
        $isRollBack = $releaseRequestNew->rr_build_version < $releaseRequestOld->rr_build_version;
        if ($isRollBack) {
            $notification = new notification\RollBackSucceed($project, $releaseRequestNew, $releaseRequestOld, [], $initiatorUserName);
        }

        return $notification;
    }

    /**
     * @param string $projectName
     * @param string $user
     * @param string $comment
     *
     * @return recipient\Email[]
     */
    protected function getReleaseRequestForbiddenRecipientList(string $projectName, string $user, string $comment): array
    {
        return [new recipient\Email($this->releaseRequestForbiddenEmail)];
    }

    /**
     * @param string $projectName
     * @param string $user
     * @param string $comment
     *
     * @return NotificationInterface
     */
    protected function getReleaseRequestForbiddenNotification(string $projectName, string $user, string $comment): NotificationInterface
    {
        return new notification\ReleaseRequestForbidden($projectName, $user, $comment);
    }
}
