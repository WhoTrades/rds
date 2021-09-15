<?php
namespace whotrades\rds\commands;

use whotrades\rds\components\Deploy\DeployEventInterface;
use whotrades\rds\components\Deploy\GenericEvent;
use whotrades\rds\events\Deploy\CronConfigAfterEvent;
use whotrades\rds\events\Deploy\UsedVersionAfterEvent;
use yii\base\Event;
use whotrades\rds\services\DeployServiceInterface;
use Yii;
use whotrades\rds\models\Log;
use whotrades\rds\models\ProjectConfigHistory;
use whotrades\RdsSystem\Cron\RabbitListener;
use yii\helpers\Url;
use whotrades\RdsSystem\Message;
use whotrades\rds\models\Build;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Project;
use whotrades\rds\helpers\WebSockets as WebSocketsHelper;
use whotrades\rds\services\NotificationServiceInterface;

/**
 * @example php yii.php deploy/index
 */
class DeployController extends RabbitListener implements DeployEventInterface
{
    /**
     * @var NotificationServiceInterface
     */
    private $notificationService;

    /** @var DeployServiceInterface */
    private $deployService;

    /**
     * {@inheritDoc}
     * @param NotificationServiceInterface $notificationService
     */
    public function __construct($id, $module, NotificationServiceInterface $notificationService, DeployServiceInterface $deployService, $config = null)
    {
        $this->notificationService = $notificationService;
        $this->deployService = $deployService;
        $this->attachEvents();

        $config = $config ?? [];
        parent::__construct($id, $module, $config);
    }

    /**
     * @return void
     */
    public function actionIndex()
    {
        $model  = $this->getMessagingModel();

        $model->readTaskStatusChanged(false, function (Message\TaskStatusChanged $message) use ($model) {
            Yii::info("Received status changed message: " . json_encode($message));
            $this->actionSetStatus($message);
        });

        $model->readCronConfig(false, function (Message\ReleaseRequestCronConfig $message) use ($model) {
            Yii::info("Received cron config message: " . json_encode($message));
            $this->deployService->setCronConfig($message);
        });

        $model->readUseError(false, function (Message\ReleaseRequestUseError $message) use ($model) {
            Yii::info("Received use error message: " . json_encode($message));
            $this->actionSetUseError($message);
        });

        $model->readUsedVersion(false, function (Message\ReleaseRequestUsedVersion $message) use ($model) {
            Yii::info("Received used version message: " . json_encode($message));
            $this->deployService->setUsedVersion($message);
        });

        $model->readProjectConfigResult(false, function (Message\ProjectConfigResult $message) use ($model) {
            Yii::info("Received project config result message: " . json_encode($message));
            $this->actionSetProjectConfigResult($message);
        });

        $model->readRemoveReleaseRequest(false, function (Message\RemoveReleaseRequest $message) use ($model) {
            Yii::info("Received remove release request message: " . json_encode($message));
            $this->actionRemoveReleaseRequest($message);
        });

        Yii::info("Start listening");

        $this->waitForMessages($model);
    }

    private function attachEvents()
    {
        Event::on(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_AFTER, function (UsedVersionAfterEvent $event) {
            // dg: Don't send notifications about child releases
            if (!$event->getReleaseRequest()->isChild()) {
                $this->notificationService->sendUsingSucceed(
                    $event->getReleaseRequest()->project,
                    $event->getReleaseRequest(),
                    $event->getReleaseRequestOld(),
                    $event->getMessage()->initiatorUserName
                );
            }
            Yii::$app->webSockets->send('updateAllReleaseRequests', []);
        });

        Event::on(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_AFTER, function (CronConfigAfterEvent $event) {
            WebSocketsHelper::sendReleaseRequestUpdated($event->getReleaseRequest()->obj_id);
        });

    }

    /**
     * @param Message\TaskStatusChanged $message
     *
     * @throws \Exception
     */
    private function actionSetStatus(Message\TaskStatusChanged $message)
    {
        $event = $this->createEvent($message);
        $this->trigger(DeployEventInterface::EVENT_TASK_STATUS_CHANGED_BEFORE, $event);
        $status = $message->status;
        $version = $message->version;
        $attach = $message->attach;

        /** @var $build Build*/
        $build = Build::findByPk($message->taskId);
        if (!$build) {
            Yii::error("Build $message->taskId not found, message=" . json_encode($message));
            $message->accepted();

            return;
        }

        $project = $build->project;

        $buildPrevStatus = $build->build_status;
        // ag: Build::STATUS_POST_INSTALLED is a technical status
        if (!in_array($status, [Build::STATUS_POST_INSTALLED])) {
            $build->build_status = $status;
        }
        if ($attach) {
            $build->build_attach .= $attach;
        }
        if ($version) {
            $build->build_version = $version;
        }

        $build->save();

        switch ($status) {
            case Build::STATUS_BUILDING:
                if (!empty($build->releaseRequest) && empty($build->releaseRequest->rr_build_started)) {
                    $build->releaseRequest->rr_status = ReleaseRequest::STATUS_BUILDING;
                    $build->releaseRequest->rr_build_started = date("Y-m-d H:i:s");
                    $build->releaseRequest->save();
                }

                break;
            case Build::STATUS_BUILT:
                if ($build->releaseRequest && $build->releaseRequest->countNotBuiltBuilds() == 0) {
                    $build->releaseRequest->rr_status = ReleaseRequest::STATUS_BUILT;
                    $build->releaseRequest->save();

                    $build->releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_BUILD_SUCCESS);

                    $build->releaseRequest->sendInstallTask();
                }

                break;
            case Build::STATUS_INSTALLING:
                if ($build->releaseRequest->rr_status !== ReleaseRequest::STATUS_INSTALLING) {
                    $build->releaseRequest->rr_status = ReleaseRequest::STATUS_INSTALLING;
                    $build->releaseRequest->save();
                }

                $build->releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_INSTALL_START);

                break;
            case Build::STATUS_INSTALLED:
                if ($build->releaseRequest && $build->releaseRequest->countNotInstalledBuilds() == 0) {
                    $build->releaseRequest->rr_status = ReleaseRequest::STATUS_INSTALLED;
                    $build->releaseRequest->rr_last_error_text = null;
                    $build->releaseRequest->rr_built_time = date("r");
                    $build->releaseRequest->save();

                    $build->releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_INSTALL_SUCCESS);

                    if ($build->releaseRequest->isChild()) {
                        $parentReleaseRequest = ReleaseRequest::findByPk($build->releaseRequest->rr_leading_id);
                    } else {
                        $parentReleaseRequest = $build->releaseRequest;
                    }

                    $releaseRequestGroupList = array_merge([$parentReleaseRequest], $parentReleaseRequest->getReleaseRequests()->all());
                    foreach ($releaseRequestGroupList as $releaseRequest) {
                        // Break after 1st not installed build status to not iterate through all set
                        if ($releaseRequest->countNotInstalledBuilds() !== 0) {
                            break 2; // break foreach & switch
                        }
                    }

                    $this->notificationService->sendInstallationSucceed(
                        $parentReleaseRequest->project->project_name,
                        $parentReleaseRequest->rr_build_version, array_map(
                        function (Build $build) {
                            return $build->obj_id;
                        },
                        $parentReleaseRequest->builds
                    ));
                }
                break;
            case Build::STATUS_POST_INSTALLED:
                // ag: Do nothing. It is a technical status
                Yii::info('Post install script success');
                break;
            case Build::STATUS_FAILED:
                switch ($buildPrevStatus) {
                    case Build::STATUS_BUILDING:
                        if ($build->releaseRequest) {
                            $build->releaseRequest->rr_status = ReleaseRequest::STATUS_FAILED;
                            $build->releaseRequest->save();

                            $build->releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_BUILD_ERROR);
                        }

                        $build->releaseRequest->increaseFailBuildCount();
                        if ($build->releaseRequest->canBeRecreatedAuto()) {
                            $build->releaseRequest->recreate(Yii::$app->params['autoReleaseRequestUserId']);
                        } else {
                            $this->notificationService->sendBuildFailed($project->project_name, $build->obj_id, $build->releaseRequest->getFailBuildCount());
                        }

                        break;
                    case Build::STATUS_INSTALLING:
                        // ag: Revert to status BUILT and add error description
                        if ($build->releaseRequest) {
                            $build->releaseRequest->rr_status = ReleaseRequest::STATUS_BUILT;
                            $build->releaseRequest->rr_last_error_text = $attach;
                            $build->releaseRequest->save();

                            $build->releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_INSTALL_ERROR);
                        }

                        $build->releaseRequest->increaseFailInstallCount();
                        if ($build->releaseRequest->canBeInstalledAuto()) {
                            $build->releaseRequest->sendInstallTask();
                        } else {
                            $this->notificationService->sendInstallationFailed($project->project_name, $build->obj_id, $build->releaseRequest->getFailInstallCount());
                        }

                        break;
                }
                break;
            case Build::STATUS_CANCELLED:
                $releaseRequest = $build->releaseRequest;
                if (!empty($releaseRequest)) {
                    $releaseRequest->rr_status = ReleaseRequest::STATUS_CANCELLED;
                    $releaseRequest->save();

                    $this->notificationService->sendInstallationCanceled($project->project_name, $build->obj_id);
                }
                break;
        }

        WebSocketsHelper::sendReleaseRequestUpdated($build->build_release_request_obj_id);
        if ($build->releaseRequest->isChild()) {
            WebSocketsHelper::sendReleaseRequestUpdated($build->releaseRequest->rr_leading_id);
        }

        $message->accepted();
        $event->build = $build;
        $this->trigger(DeployEventInterface::EVENT_TASK_STATUS_CHANGED_AFTER, $event);
    }

    /**
     * @param Message\ReleaseRequestUseError $message
     */
    private function actionSetUseError(Message\ReleaseRequestUseError $message)
    {
        $event = $this->createEvent($message);
        $this->trigger(DeployEventInterface::EVENT_TASK_USE_ERROR_BEFORE, $event);
        $releaseRequest = ReleaseRequest::findByPk($message->releaseRequestId);
        if (!$releaseRequest) {
            Yii::error("Release Request #$message->releaseRequestId not found");
            $message->accepted();

            return;
        }

        foreach ($releaseRequest->builds as $build) {
            $build->build_attach .= "\n\n=== Begin Use Error Log ===\n\n";
            $build->build_attach .= $message->text;
            $build->build_attach .= "\n\n=== End Use Error Log ===";
            $build->save();
        }

        $oldReleaseRequest = ReleaseRequest::getUsedReleaseByProjectId($releaseRequest->project->obj_id);
        if ($oldReleaseRequest) {
            $oldReleaseRequest->setOld();
        }

        $releaseRequest->rr_last_error_text = $message->text;
        $releaseRequest->rr_status = ReleaseRequest::STATUS_INSTALLED;
        $releaseRequest->save();

        Log::createLogMessage("Use error at release request {$releaseRequest->getTitle()}: " . $message->text, $message->initiatorUserName);

        if ($releaseRequest->isChild()) {
            $mainReleaseRequest = ReleaseRequest::findByPk($releaseRequest->rr_leading_id);
        } else {
            $mainReleaseRequest = $releaseRequest;
        }

        if ($mainReleaseRequest) {
            $oldMainReleaseRequest = $mainReleaseRequest->getOldReleaseRequest();
            if ($oldMainReleaseRequest->canBeUsed()) {
                $oldMainReleaseRequest->sendUseTasks($message->initiatorUserName);
            }
        }

        $releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_USING_ERROR);

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $message->accepted();
        $this->trigger(DeployEventInterface::EVENT_TASK_USE_ERROR_AFTER, $event);
    }

    /**
     * @param Message\ProjectConfigResult $message
     */
    private function actionSetProjectConfigResult(Message\ProjectConfigResult $message)
    {
        $event = $this->createEvent($message);
        $this->trigger(DeployEventInterface::EVENT_PROJECT_CONFIG_RESULT_BEFORE, $event);
        if (!isset($message->projectConfigHistoryId)) {
            Yii::warning('Skip processing message with NULL projectConfigHistoryId');
            $message->accepted();

            return;
        }

        $projectConfigHistory = ProjectConfigHistory::findByPk($message->projectConfigHistoryId);

        if (!$projectConfigHistory) {
            Yii::warning("Skip processing message. ProjectConfigHistory with id={$message->projectConfigHistoryId} doesn't exist");
            $message->accepted();

            return;
        }

        $projectConfigHistory->pch_log .= $message->log;
        $projectConfigHistory->save();

        $message->accepted();
        $this->trigger(DeployEventInterface::EVENT_PROJECT_CONFIG_RESULT_AFTER, $event);
    }

    /**
     * @param Message\RemoveReleaseRequest $message
     */
    private function actionRemoveReleaseRequest(Message\RemoveReleaseRequest $message)
    {
        $event = $this->createEvent($message);
        $this->trigger(DeployEventInterface::EVENT_REMOVE_RELEASE_REQUEST_BEFORE, $event);
        $project = Project::findByAttributes(['project_name' => $message->projectName]);
        if (!$project) {
            $message->accepted();
            Yii::info("Skipped removing release request $message->projectName-$message->version as project not exists");

            return;
        }

        $rr = ReleaseRequest::findByAttributes([
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $message->version,
        ]);

        if ($rr) {
            Yii::info("Removed release request $message->projectName-$message->version");
            $rr->markAsDestroyed();
        }
        Yii::info("Skipped removing release request $message->projectName-$message->version as not exists");

        $message->accepted();
        $this->trigger(DeployEventInterface::EVENT_REMOVE_RELEASE_REQUEST_AFTER, $event);
    }

    /**
     * @param string $route
     * @param array $params
     *
     * @return mixed
     */
    public function createUrl($route, $params)
    {
        Yii::$app->urlManager->setBaseUrl('');
        array_unshift($params, $route);

        return Url::to($params, true);
    }

    /**
     * @param Message\Base $message
     *
     * @return GenericEvent
     */
    protected function createEvent(Message\Base $message): GenericEvent
    {
        $event = new GenericEvent();
        $event->message = $message;
        return $event;
    }
}
