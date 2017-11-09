<?php
namespace whotrades\rds\commands;

use app\modules\Whotrades\commands\DevParseCronConfigController;
use app\modules\Whotrades\models\ToolJob;
use whotrades\rds\components\Status;
use whotrades\rds\models\JiraUse;
use whotrades\rds\models\Log;
use whotrades\RdsSystem\Cron\RabbitListener;
use Yii;
use yii\helpers\Url;
use whotrades\RdsSystem\Message;
use whotrades\rds\models\Build;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Project;
use app\modules\Wtflow\models\HardMigration;
use whotrades\rds\models\Worker;
use whotrades\rds\models\Project2worker;
use app\modules\Wtflow\models\JiraNotificationQueue;

/**
 * @example php yii.php deploy/index
 */
class DeployController extends RabbitListener
{
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

        $model->readMigrations(false, function (Message\ReleaseRequestMigrations $message) use ($model) {
            Yii::info("Received migrations message: " . json_encode($message));
            $this->actionSetMigrations($message);
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

        $model->readRemoveReleaseRequest(false, function (Message\RemoveReleaseRequest $message) use ($model) {
            Yii::info("Received remove release request message: " . json_encode($message));
            $this->actionRemoveReleaseRequest($message);
        });

        $model->readMigrationStatus(false, function (Message\ReleaseRequestMigrationStatus $message) use ($model) {
            Yii::info("env={$model->getEnv()}, Received request of release request status: " . json_encode($message));
            $this->actionSetMigrationStatus($message);
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

        $build->build_status = $status;
        if ($attach) {
            $build->build_attach .= $attach;
        }
        if ($version) {
            $build->build_version = $version;
        }

        $build->save();

        switch ($status) {
            case Build::STATUS_INSTALLED:
                if ($build->releaseRequest && $build->releaseRequest->countNotFinishedBuilds() == 0) {
                    $builds = $build->releaseRequest->builds;
                    $build->releaseRequest->rr_status = ReleaseRequest::STATUS_INSTALLED;
                    $build->releaseRequest->rr_built_time = date("r");
                    $build->releaseRequest->save();
                    $title = "Success installed $project->project_name v.$version";
                    $text = "Проект $project->project_name был собран и разложен по серверам.<br />";
                    foreach ($builds as $val) {
                        $text .= "<a href='" .
                            Url::to(['build/view', 'id' => $val->obj_id]) .
                            "'>Подробнее {$val->worker->worker_name} v.{$val->build_version}</a><br />";
                    }

                    Yii::$app->EmailNotifier->sendReleaseRejectCustomNotification($title, $text);
                    foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
                        if (!$phone) {
                            continue;
                        }
                        Yii::$app->smsSender->sendSms($phone, $title);
                    }
                }
                break;
            case Build::STATUS_FAILED:
                $title = "Failed to install $project->project_name";
                $text = "Проект $project->project_name не удалось собрать. <a href='" .
                    Url::to(['build/view', 'id' => $build->obj_id]) .
                    "'>Подробнее</a>";

                Yii::$app->EmailNotifier->sendReleaseRejectCustomNotification($title, $text);
                foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
                    if (!$phone) {
                        continue;
                    }
                    Yii::$app->smsSender->sendSms($phone, $title);
                }
                $releaseRequest = $build->releaseRequest;
                if (!empty($releaseRequest)) {
                    $releaseRequest->rr_status = ReleaseRequest::STATUS_FAILED;
                    $releaseRequest->save();
                }
                break;
            case Build::STATUS_BUILDING:
                if (!empty($build->releaseRequest) && empty($build->releaseRequest->rr_build_started)) {
                    $build->releaseRequest->rr_build_started = date("Y-m-d H:i:s");
                    $build->releaseRequest->save();
                }
                break;
            case Build::STATUS_CANCELLED:
                $title = "Cancelled installation of $project->project_name";
                $text = "Сборка $project->project_name отменена. <a href='" .
                    Url::to(['build/view', 'id' => $build->obj_id], true) .
                    "'>Подробнее</a>";

                $releaseRequest = $build->releaseRequest;
                if (!empty($releaseRequest)) {
                    $releaseRequest->rr_status = ReleaseRequest::STATUS_CANCELLED;
                    $releaseRequest->save();

                    Yii::$app->EmailNotifier->sendReleaseRejectCustomNotification($title, $text);
                    foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
                        if (!$phone) {
                            continue;
                        }
                        Yii::$app->smsSender->sendSms($phone, $title);
                    }
                }
                break;
        }

        self::sendReleaseRequestUpdated($build->build_release_request_obj_id);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestMigrations $message
     *
     * @throws \Exception
     */
    private function actionSetMigrations(Message\ReleaseRequestMigrations $message)
    {
        /** @var $project Project */
        $project = Project::findByAttributes(['project_name' => $message->project]);
        if (!$project) {
            Yii::error(404, 'Project not found');
            $message->accepted();

            return;
        }

        /** @var $releaseRequest ReleaseRequest */
        $releaseRequest = ReleaseRequest::findByAttributes(array(
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $message->version,
        ));
        if (!$releaseRequest) {
            Yii::error('Release request not found');
            $message->accepted();

            return;
        }
        if ($message->type == 'pre') {
            $releaseRequest->rr_new_migration_count = count($message->migrations);
            $releaseRequest->rr_new_migrations = json_encode($message->migrations);
        } elseif ($message->type == 'post') {
            $releaseRequest->rr_new_post_migrations = json_encode($message->migrations);
        } else {
            foreach ($message->migrations as $migration) {
                list($migration, $ticket) = preg_split('~\s+~', $migration);

                $hm = new HardMigration();
                $hm->attributes = [
                    'migration_release_request_obj_id' => $releaseRequest->obj_id,
                    'migration_project_obj_id' => $releaseRequest->rr_project_obj_id,
                    'migration_type' => 'hard',
                    'migration_name' => $migration,
                    'migration_ticket' => str_replace('#', '', $ticket),
                    'migration_status' => HardMigration::MIGRATION_STATUS_NEW,
                    'migration_environment' => 'main',
                ];

                if (!$hm->save()) {
                    if (count($hm->errors) != 1 || !isset($hm->errors["migration_name"])) {
                        Yii::error("Can't save HardMigration: " . json_encode($hm->errors));
                    } else {
                        Yii::info("Skip migration $migration as already exists in DB (" . json_encode($hm->errors) . ")");
                    }
                }
            }
        }

        $releaseRequest->save(false);

        self::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestCronConfig $message
     */
    private function actionSetCronConfig(Message\ReleaseRequestCronConfig $message)
    {
        $transaction = \Yii::$app->db->beginTransaction();
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

            self::sendReleaseRequestUpdated($releaseRequest->obj_id);

            $transaction->commit();

            $message->accepted();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param Message\ReleaseRequestUseError $message
     */
    private function actionSetUseError(Message\ReleaseRequestUseError $message)
    {
        $releaseRequest = ReleaseRequest::findByPk($message->releaseRequestId);
        if (!$releaseRequest) {
            Yii::error("Release Request #$message->releaseRequestId not found");
            $message->accepted();

            return;
        }
        $releaseRequest->rr_use_text = $message->text;
        $releaseRequest->rr_status = ReleaseRequest::STATUS_INSTALLED;
        $releaseRequest->save();

        Log::createLogMessage("Use error at release request {$releaseRequest->getTitle()}: " . $message->text, $message->initiatorUserName);

        self::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestUsedVersion $message
     */
    private function actionSetUsedVersion(Message\ReleaseRequestUsedVersion $message)
    {
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
            $project->project_current_version = $message->version;
            $project->save(false);

            $oldUsed = ReleaseRequest::findByAttributes(array(
                'rr_status' => ReleaseRequest::STATUS_USED,
                'rr_project_obj_id' => $project->obj_id,
            ));

            if ($oldUsed) {
                $oldUsed->rr_status = ReleaseRequest::STATUS_OLD;
                $oldUsed->rr_last_time_on_prod = date("r");
                $oldUsed->rr_revert_after_time = null;
                $oldUsed->save(false);

                //self::sendReleaseRequestUpdated($oldUsed->obj_id);
            }

            if ($releaseRequest) {
                $releaseRequest->rr_use_text = null;
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

            if (Yii::$app->hasModule('Wtflow')) {
                // an: Асинхронная задача на email/sms/... оповещение
                $notificationItem = new JiraNotificationQueue();
                $notificationItem->jnq_project_obj_id = $project->obj_id;
                $notificationItem->jnq_old_version = $oldVersion;
                $notificationItem->jnq_new_version = $message->version;
                if (!$notificationItem->save()) {
                    Yii::error("Can't send notification task: " . json_encode($notificationItem->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }
        }

        $transaction->commit();

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $message->accepted();
    }

    /**
     * @param Message\RemoveReleaseRequest $message
     */
    private function actionRemoveReleaseRequest(Message\RemoveReleaseRequest $message)
    {
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
    }

    /**
     * @param Message\ReleaseRequestMigrationStatus $message
     */
    private function actionSetMigrationStatus(Message\ReleaseRequestMigrationStatus $message)
    {
        $projectObj = Project::findByAttributes(array('project_name' => $message->project));

        if (!$projectObj) {
            Yii::error('unknown project ' . $message->project);
            $message->accepted();

            return;
        }
        $releaseRequest = ReleaseRequest::findByAttributes(array('rr_build_version' => $message->version, 'rr_project_obj_id' => $projectObj->obj_id));

        if (!$releaseRequest) {
            Yii::error('unknown release request: project=' . $message->project . ", build_version=" . $message->version);
            $message->accepted();

            return;
        }

        $transaction = \Yii::$app->db->beginTransaction();
        if ($message->type == 'pre') {
            $releaseRequest->rr_migration_status = $message->status;
            $releaseRequest->rr_migration_error = $message->errorText;

            if ($message->status == ReleaseRequest::MIGRATION_STATUS_UP) {
                $releaseRequest->rr_new_migration_count = 0;

                ReleaseRequest::updateAll(['rr_migration_status' => $message->status, 'rr_new_migration_count' => 0], 'rr_build_version <= :version AND rr_project_obj_id = :id', [
                    ':version'  => $message->version,
                    ':id'       => $projectObj->obj_id,
                ]);
            }
        } else {
            $releaseRequest->rr_post_migration_status = $message->status;

            if ($message->status == ReleaseRequest::MIGRATION_STATUS_UP) {
                ReleaseRequest::updateAll(['rr_migration_status' => $message->status], 'rr_build_version <= :version AND rr_project_obj_id = :id', [
                    ':version'  => $message->version,
                    ':id'       => $projectObj->obj_id,
                ]);
            }
        }

        $releaseRequest->save();
        $transaction->commit();

        static::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param int $id
     */
    public static function sendReleaseRequestUpdated($id)
    {
        if (!$releaseRequest = ReleaseRequest::findByPk($id)) {
            return;
        }

        Yii::info("Sending to comet new data of releaseRequest $id");

        $html = \Yii::$app->view->renderFile('@app/views/site/_releaseRequestGrid.php', [
            'dataProvider' => $releaseRequest->search(['obj_id' => $id]),
            'filterModel' => new ReleaseRequest(),
        ]);

        Yii::$app->webSockets->send('releaseRequestChanged', ['rr_id' => $id, 'html' => $html]);
        Yii::info("Sent");
    }

    /**
     * @param string $route
     * @param array $params
     *
     * @return mixed
     */
    public function createUrl($route, $params)
    {
        \Yii::$app->urlManager->setBaseUrl('');
        array_unshift($params, $route);

        return Url::to($params, true);
    }
}
