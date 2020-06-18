<?php
namespace whotrades\rds\commands;

use whotrades\rds\components\Deploy\DeployEventInterface;
use whotrades\rds\components\Deploy\GenericEvent;
use Yii;
use app\modules\Whotrades\commands\DevParseCronConfigController;
use app\modules\Whotrades\models\ToolJob;
use whotrades\rds\components\Status;
use whotrades\rds\models\JiraUse;
use whotrades\rds\models\Log;
use whotrades\rds\models\ProjectConfigHistory;
use whotrades\RdsSystem\Cron\RabbitListener;
use yii\helpers\Url;
use whotrades\RdsSystem\Message;
use whotrades\rds\models\Build;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Project;
use whotrades\rds\models\Worker;
use whotrades\rds\models\Project2worker;
use whotrades\rds\helpers\WebSockets as WebSocketsHelper;
use whotrades\rds\services\NotificationServiceInterface;
use Exception;

/**
 * @example php yii.php deploy/index
 */
class DeployController extends RabbitListener implements DeployEventInterface
{
    /**
     * @var NotificationServiceInterface
     */
    private $notificationService;

    /**
     * {@inheritDoc}
     * @param NotificationServiceInterface $notificationService
     */
    public function __construct($id, $module, NotificationServiceInterface $notificationService, $config = null)
    {
        $this->notificationService = $notificationService;

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
            $this->actionSetCronConfig($message);
        });

        $model->readUseError(false, function (Message\ReleaseRequestUseError $message) use ($model) {
            Yii::info("Received use error message: " . json_encode($message));
            $this->actionSetUseError($message);
        });

        $model->readUsedVersion(false, function (Message\ReleaseRequestUsedVersion $message) use ($model) {
            Yii::info("Received used version message: " . json_encode($message));
            $this->actionSetUsedVersion($message);
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

                    if (!$build->releaseRequest->isChild()) {
                        $buildIdList = array_map(
                            function ($build) {
                                return $build->obj_id;
                            },
                            $build->releaseRequest->builds
                        );

                        $this->notificationService->sendInstallationSucceed($project->project_name, $version, $buildIdList);
                    }
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

        $message->accepted();
        $event->build = $build;
        $this->trigger(DeployEventInterface::EVENT_TASK_STATUS_CHANGED_AFTER, $event);
    }

    /**
     * @param Message\ReleaseRequestCronConfig $message
     */
    private function actionSetCronConfig(Message\ReleaseRequestCronConfig $message)
    {
        $event = $this->createEvent($message);
        $this->trigger(DeployEventInterface::EVENT_CRON_CONFIG_BEFORE, $event);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            /** @var $build Build */
            $build = Build::findByPk($message->taskId);

            if (!$build) {
                Yii::error("Build #$message->taskId not found");
                $message->accepted();
                $transaction->rollBack();

                return;
            }
            $releaseRequest = $build->releaseRequest;

            if (!$releaseRequest) {
                Yii::error("Build #$message->taskId has no release request");
                $message->accepted();
                $transaction->rollBack();

                return;
            }

            $releaseRequest->rr_cron_config = $message->text;

            $releaseRequest->save(false);

            if (class_exists(DevParseCronConfigController::class)) {
                // ag: Delete existed ToolJob for this build_version. It is needed when we recreate ReleaseRequest
                ToolJob::deleteAll(
                    'project_obj_id=:id AND "version"=:version',
                    [
                        ':id' => $releaseRequest->rr_project_obj_id,
                        ':version' => $releaseRequest->rr_build_version,
                    ]
                );

                DevParseCronConfigController::parseCronConfig($releaseRequest);

                ToolJob::updateAll(
                    [
                        'obj_status_did' => Status::DELETED,
                    ],
                    'project_obj_id=:id AND "version"=:version',
                    [
                        ':id' => $releaseRequest->rr_project_obj_id,
                        ':version' => $releaseRequest->rr_build_version,
                    ]
                );
            }

            WebSocketsHelper::sendReleaseRequestUpdated($releaseRequest->obj_id);

            $transaction->commit();

            $message->accepted();
            $this->trigger(DeployEventInterface::EVENT_CRON_CONFIG_AFTER, $event);
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
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
        }

        if ($oldMainReleaseRequest && $oldMainReleaseRequest->canBeUsed()) {
            $oldMainReleaseRequest->sendUseTasks(Yii::$app->user->getIdentity()->username);
        }

        $releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_USING_ERROR);

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $message->accepted();
        $this->trigger(DeployEventInterface::EVENT_TASK_USE_ERROR_AFTER, $event);
    }

    /**
     * @param Message\ReleaseRequestUsedVersion $message
     */
    private function actionSetUsedVersion(Message\ReleaseRequestUsedVersion $message)
    {
        $event = $this->createEvent($message);
        $this->trigger(DeployEventInterface::EVENT_USED_VERSION_BEFORE, $event);
        $worker = Worker::findByAttributes(array('worker_name' => $message->worker));
        if (!$worker) {
            Yii::error("Worker $message->worker not found");
            $message->accepted();

            return;
        }

        /** @var $project Project */
        $project = Project::findByAttributes(array('project_name' => $message->project));
        if (!$project) {
            Yii::error("Project $message->project not found");
            $message->accepted();

            return;
        }

        $transaction = $project->getDbConnection()->beginTransaction();

        /** @var $releaseRequest ReleaseRequest */
        $releaseRequest = ReleaseRequest::findByAttributes(array(
            'rr_build_version' => $message->version,
            'rr_project_obj_id' => $project->obj_id,
        ));

        foreach ($releaseRequest->builds as $build) {
            $build->build_attach .= "\n\n=== Begin Use Log ===\n\n";
            $build->build_attach .= $message->text;
            $build->build_attach .= "\n\n=== End Use Log ===";
            $build->save();
        }

        $builds = Build::findAllByAttributes(array(
            'build_project_obj_id' => $project->obj_id,
            'build_worker_obj_id' => $worker->obj_id,
            'build_status' => Build::STATUS_USED,
        ));

        foreach ($builds as $build) {
            /** @var $build Build */
            $build->build_status = Build::STATUS_INSTALLED;
            $build->save();
        }

        if ($releaseRequest) {
            $build = Build::findByAttributes(array(
                'build_project_obj_id' => $project->obj_id,
                'build_worker_obj_id' => $worker->obj_id,
                'build_release_request_obj_id' => $releaseRequest->obj_id,
            ));
            if ($build) {
                $build->build_status = Build::STATUS_USED;
                $build->save();
            } else {
                Yii::$app->sentry->captureMessage('unknown_build_info', [
                    'build_project_obj_id' => $project->obj_id,
                    'build_worker_obj_id' => $worker->obj_id,
                    'build_release_request_obj_id' => $releaseRequest->obj_id,
                    'message' => $message,
                ]);
            }
        }

        /** @var $p2w Project2worker */
        $p2w = Project2worker::findByAttributes(array(
            'worker_obj_id' => $worker->obj_id,
            'project_obj_id' => $project->obj_id,
        ));
        if ($p2w) {
            $p2w->p2w_current_version = $message->version;
            $p2w->save();
        }
        $list = Project2worker::findAllByAttributes(array(
            'project_obj_id' => $project->obj_id,
        ));
        $ok = true;
        foreach ($list as $p2w) {
            if ($p2w->p2w_current_version != $message->version) {
                $ok = false;
                break;
            }
        }

        if ($ok) {
            $oldVersion = $project->project_current_version;
            $project->updateCurrentVersion($message->version);

            $oldUsed = ReleaseRequest::getUsedReleaseByProjectId($project->obj_id);

            if ($oldUsed) {
                $oldUsed->rr_status = ReleaseRequest::STATUS_OLD;
                $oldUsed->rr_last_time_on_prod = date("r");
                $oldUsed->rr_revert_after_time = null;
                $oldUsed->save(false);

                //WebSocketsHelper::sendReleaseRequestUpdated($oldUsed->obj_id);
            }

            if ($releaseRequest) {
                $releaseRequest->rr_last_error_text = null;
                $releaseRequest->rr_status = ReleaseRequest::STATUS_USED;
                $releaseRequest->save(false);

                $jiraUse = new JiraUse();
                $jiraUse->attributes = [
                    'jira_use_from_build_tag' => $project->project_name . '-' . $oldVersion,
                    'jira_use_to_build_tag' => $releaseRequest->getBuildTag(),
                    'jira_use_initiator_user_name' => $message->initiatorUserName,
                ];
                $jiraUse->save();
            }

            ToolJob::updateAll(
                [
                    'obj_status_did' => Status::DELETED,
                ],
                "project_obj_id=:id",
                [
                    ':id' => $releaseRequest->rr_project_obj_id,
                ]
            );

            ToolJob::updateAll(
                [
                    'obj_status_did' => Status::ACTIVE,
                ],
                'project_obj_id=:id AND "version"=:version',
                [
                    ':id' => $releaseRequest->rr_project_obj_id,
                    ':version' => $releaseRequest->rr_build_version,
                ]
            );

            $event->projectOldVersion = $oldVersion;

            // dg: Не отправляем уведомления о дочерних релизах. Как правило там те же задачи
            if (!$releaseRequest->isChild()) {
                $this->notificationService->sendUsingSucceed($project, $releaseRequest, $oldUsed);
            }
        }

        $releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_USING_SUCCESS);

        $transaction->commit();

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $message->accepted();
        $event->build = $build;
        $event->project = $project;
        $this->trigger(DeployEventInterface::EVENT_USED_VERSION_AFTER, $event);
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
