<?php
use yii\helpers\Url;
use RdsSystem\Message;
use RdsSystem\Model\Rabbit\MessagingRdsMs;
use app\models\Build;
use app\models\ReleaseRequest;
use app\modules\Wtflow\models\JiraCreateVersion;
use app\models\Project;
use app\modules\Wtflow\models\JiraCommit;
use app\modules\Wtflow\models\HardMigration;
use app\models\ToolJob;
use app\models\Worker;
use app\models\Project2worker;
use app\modules\Wtflow\models\JiraUse;
use app\modules\Wtflow\models\JiraNotificationQueue;
use app\controllers\StatisticController;

/**
 * @example dev/services/rds/misc/tools/runner.php --tool=AsyncReader_Deploy -vv
 */
class Cronjob_Tool_AsyncReader_Deploy extends RdsSystem\Cron\RabbitDaemon
{
    /**
     * Use this function to get command line spec for cronjob
     * @return array
     */
    public static function getCommandLineSpec()
    {
        return array() + parent::getCommandLineSpec();
    }

    /**
     * @param \Cronjob\ICronjob $cronJob
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $model  = $this->getMessagingModel($cronJob);

        $model->readTaskStatusChanged(false, function (Message\TaskStatusChanged $message) use ($model) {
            $this->debugLogger->message("Received status changed message: " . json_encode($message));
            $this->actionSetStatus($message, $model);
        });

        $model->readBuildPatch(false, function (Message\ReleaseRequestBuildPatch $message) use ($model) {
            $this->debugLogger->message("Received build patch message: " . json_encode($message));
            $this->actionSetBuildPatch($message, $model);
        });

        $model->readMigrations(false, function (Message\ReleaseRequestMigrations $message) use ($model) {
            $this->debugLogger->message("Received migrations message: " . json_encode($message));
            $this->actionSetMigrations($message, $model);
        });

        $model->readCronConfig(false, function (Message\ReleaseRequestCronConfig $message) use ($model) {
            $this->debugLogger->message("Received cron config message: " . json_encode($message));
            $this->actionSetCronConfig($message, $model);
        });

        $model->readUseError(false, function (Message\ReleaseRequestUseError $message) use ($model) {
            $this->debugLogger->message("Received use error message: " . json_encode($message));
            $this->actionSetUseError($message, $model);
        });

        $model->readOldVersion(false, function (Message\ReleaseRequestOldVersion $message) use ($model) {
            $this->debugLogger->message("Received old version message: " . json_encode($message));
            $this->actionSetOldVersion($message, $model);
        });

        $model->readUsedVersion(false, function (Message\ReleaseRequestUsedVersion $message) use ($model) {
            $this->debugLogger->message("Received used version message: " . json_encode($message));
            $this->actionSetUsedVersion($message, $model);
        });

        $model->readCurrentStatusRequest(false, function (Message\ReleaseRequestCurrentStatusRequest $message) use ($model) {
            $this->debugLogger->message("Received request of release request status: " . json_encode($message));
            $this->replyToCurrentStatusRequest($message, $model);
        });

        $model->readGetProjectsRequest(false, function (Message\ProjectsRequest $message) use ($model) {
            $this->debugLogger->message("Received request of projects list: " . json_encode($message));
            $this->actionReplyProjectsList($message, $model);
        });

        $model->readGetProjectBuildsToDeleteRequest(false, function (Message\ProjectBuildsToDeleteRequest $message) use ($model) {
            $this->debugLogger->message("Received request of projects to delete: " . json_encode($message));
            $this->actionReplyProjectsBuildsToDelete($message, $model);
        });

        $model->readRemoveReleaseRequest(false, function (Message\RemoveReleaseRequest $message) use ($model) {
            $this->debugLogger->message("Received remove release request message: " . json_encode($message));
            $this->actionRemoveReleaseRequest($message, $model);
        });

        $model->readMigrationStatus(false, function (Message\ReleaseRequestMigrationStatus $message) use ($model) {
            $this->debugLogger->message("env={$model->getEnv()}, Received request of release request status: " . json_encode($message));
            $this->actionSetMigrationStatus($message, $model);
        });

        $this->debugLogger->message("Start listening");

        $this->waitForMessages($model, $cronJob);
    }

    /**
     * @param Message\TaskStatusChanged $message
     * @param MessagingRdsMs            $model
     *
     * @throws Exception
     */
    public function actionSetStatus(Message\TaskStatusChanged $message, MessagingRdsMs $model)
    {
        $status = $message->status;
        $version = $message->version;
        $attach = $message->attach;

        /** @var $build Build*/
        $build = Build::findByPk($message->taskId);
        if (!$build) {
            $this->debugLogger->error("Build $message->taskId not found, message=" . json_encode($message));
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
                            Url::to(['build/view', 'id' => $val->obj_id], true) .
                            "'>Подробнее {$val->worker->worker_name} v.{$val->build_version}</a><br />";
                    }

                    foreach (Yii::$app->params['jiraProjects'] as $jiraProject) {
                        $jiraVersion = new JiraCreateVersion();
                        $jiraVersion->attributes = [
                            'jira_name' => $project->project_name . "-" . $build->releaseRequest->rr_build_version,
                            'jira_description' => 'Сборка #' . $build->build_release_request_obj_id . ', ' . $build->releaseRequest->user->email . ' [auto]',
                            'jira_project' => $jiraProject,
                            'jira_archived' => true,
                            'jira_released' => false,
                        ];

                        $jiraVersion->save(false);
                    }

                    Yii::$app->EmailNotifier->sendReleaseRejectCustomNotification($title, $text);
                    foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
                        if (!$phone) {
                            continue;
                        }
                        Yii::$app->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                    }
                }
                break;
            case Build::STATUS_FAILED:
                $title = "Failed to install $project->project_name";
                $text = "Проект $project->project_name не удалось собрать. <a href='" .
                    Url::to(['build/view', 'id' => $build->obj_id], true) .
                    "'>Подробнее</a>";

                Yii::$app->EmailNotifier->sendReleaseRejectCustomNotification($title, $text);
                foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
                    if (!$phone) {
                        continue;
                    }
                    Yii::$app->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                }
                $releaseRequest = $build->releaseRequest;
                $releaseRequest->rr_status = ReleaseRequest::STATUS_FAILED;
                $releaseRequest->save();
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
                $releaseRequest->rr_status = ReleaseRequest::STATUS_CANCELLED;
                $releaseRequest->save();

                Yii::$app->EmailNotifier->sendReleaseRejectCustomNotification($title, $text);
                foreach (explode(",", \Yii::$app->params['notify']['status']['phones']) as $phone) {
                    if (!$phone) {
                        continue;
                    }
                    Yii::$app->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                }
                break;
        }

        self::sendReleaseRequestUpdated($build->build_release_request_obj_id);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestBuildPatch $message
     * @param MessagingRdsMs                   $model
     *
     * @throws Exception
     */
    public function actionSetBuildPatch(Message\ReleaseRequestBuildPatch $message, MessagingRdsMs $model)
    {
        if (!$Project = Project::findByAttributes(['project_name' => $message->project])) {
            $this->debugLogger->error("Project $message->project not found");
            $message->accepted();

            return;
        }

        $releaseRequest = ReleaseRequest::findByAttributes([
            'rr_project_obj_id' => $Project->obj_id,
            'rr_build_version' => $message->version,
        ]);

        if (!$releaseRequest) {
            $this->debugLogger->error("Release request of project=$message->project, version=$message->version not found");
            $message->accepted();

            return;
        }

        $lines = explode("\n", str_replace("\r", "", $message->output));

        $repository = null;
        foreach ($lines as $line) {
            $this->debugLogger->debug("Processing $line");
            if (preg_match('~>>> origin\s*ssh://(?:\w+@)?git2?.whotrades.net/srv/git/([\w-]+)\s~', $line, $ans)) {
                $repository = $ans[1];
                $this->debugLogger->message("Repo: $repository");
                continue;
            }
            if (preg_match('~^\s*(?<hash>\w+)\|(?<comment>.*)\|/(?<author>.*?)/$~', $line, $matches)) {
                $commit = new JiraCommit();
                if (preg_match_all('~#(WT\w+-\d+)~', $matches['comment'], $ans)) {
                    foreach ($ans[1] as $val2) {
                        $commit->attributes = [
                            'jira_commit_build_tag' => $releaseRequest->getBuildTag(),
                            'jira_commit_hash' => $matches['hash'],
                            'jira_commit_author' => $matches['author'],
                            'jira_commit_comment' => $matches['comment'],
                            'jira_commit_ticket' => $val2,
                            'jira_commit_project' => explode('-', $val2)[0],
                            'jira_commit_repository' => $repository,
                        ];

                        $commit->save();
                    }
                }
            }
        }

        self::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestMigrations $message
     * @param MessagingRdsMs                   $model
     *
     * @throws Exception
     */
    public function actionSetMigrations(Message\ReleaseRequestMigrations $message, MessagingRdsMs $model)
    {
        /** @var $project Project */
        $project = Project::findByAttributes(['project_name' => $message->project]);
        if (!$project) {
            $this->debugLogger->error(404, 'Project not found');
            $message->accepted();

            return;
        }

        /** @var $releaseRequest ReleaseRequest */
        $releaseRequest = ReleaseRequest::findByAttributes(array(
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $message->version,
        ));
        if (!$releaseRequest) {
            $this->debugLogger->error('Release request not found');
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

                foreach (Yii::$app->params['environments'] as $env) {
                    $this->debugLogger->message("Adding migration $migration with env=$env");
                    $hm = new HardMigration();
                    $hm->attributes = [
                        'migration_release_request_obj_id' => $releaseRequest->obj_id,
                        'migration_project_obj_id' => $releaseRequest->rr_project_obj_id,
                        'migration_type' => 'hard',
                        'migration_name' => $migration,
                        'migration_ticket' => str_replace('#', '', $ticket),
                        'migration_status' => HardMigration::MIGRATION_STATUS_NEW,
                        'migration_environment' => $env,
                    ];
                    if (!$hm->save()) {
                        if (count($hm->errors) != 1 || !isset($hm->errors["migration_name"])) {
                            $this->debugLogger->error("Can't save HardMigration: " . json_encode($hm->errors));
                        } else {
                            $this->debugLogger->message("Skip migration $migration as already exists in DB (" . json_encode($hm->errors) . ")");
                        }
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
     * @param MessagingRdsMs                   $model
     *
     * @throws CDbException
     * @throws Exception
     */
    public function actionSetCronConfig(Message\ReleaseRequestCronConfig $message, MessagingRdsMs $model)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            /** @var $build Build */
            $build = Build::findByPk($message->taskId);

            if (!$build) {
                $this->debugLogger->error("Build #$message->taskId not found");
                $message->accepted();

                return;
            }
            $releaseRequest = $build->releaseRequest;

            if (!$releaseRequest) {
                $this->debugLogger->error("Build #$message->taskId has no release request");
                $message->accepted();

                return;
            }

            $releaseRequest->rr_cron_config = $message->text;

            $releaseRequest->save(false);

            $releaseRequest->parseCronConfig();

            ToolJob::updateAll(
                [
                    'obj_status_did' => \ServiceBase_IHasStatus::STATUS_DELETED,
                ],
                'project_obj_id=:id AND "version"=:version',
                [
                    ':id' => $releaseRequest->rr_project_obj_id,
                    ':version' => $releaseRequest->rr_build_version,
                ]
            );

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
     * @param MessagingRdsMs                 $model
     *
     * @throws Exception
     */
    public function actionSetUseError(Message\ReleaseRequestUseError $message, MessagingRdsMs $model)
    {
        $releaseRequest = ReleaseRequest::findByPk($message->releaseRequestId);
        if (!$releaseRequest) {
            $this->debugLogger->error("Release Request #$message->releaseRequestId not found");
            $message->accepted();

            return;
        }
        $releaseRequest->rr_use_text = $message->text;
        $releaseRequest->rr_status = ReleaseRequest::STATUS_FAILED;
        $releaseRequest->save();

        self::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestOldVersion $message
     * @param MessagingRdsMs                   $model
     *
     * @throws Exception
     */
    public function actionSetOldVersion(Message\ReleaseRequestOldVersion $message, MessagingRdsMs $model)
    {
        $releaseRequest = ReleaseRequest::findByPk($message->releaseRequestId);

        if (!$releaseRequest) {
            $this->debugLogger->error("Release Request #$message->releaseRequestId not found");
            $message->accepted();

            return;
        }

        if (!$releaseRequest->rr_old_version) {
            $releaseRequest->rr_old_version = $message->version;
            $releaseRequest->save(false);

            self::sendReleaseRequestUpdated($releaseRequest->obj_id);
        }

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestUsedVersion $message
     * @param MessagingRdsMs                    $model
     *
     * @throws CDbException
     * @throws Exception
     */
    public function actionSetUsedVersion(Message\ReleaseRequestUsedVersion $message, MessagingRdsMs $model)
    {
        $worker = Worker::findByAttributes(array('worker_name' => $message->worker));
        if (!$worker) {
            $this->debugLogger->error("Worker $message->worker not found");
            $message->accepted();

            return;
        }

        /** @var $project Project */
        $project = Project::findByAttributes(array('project_name' => $message->project));
        if (!$project) {
            $this->debugLogger->error("Project $message->project not found");
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
                $this->debugLogger->dump()->message('an', 'unknown_build_info', false, [
                    'build_project_obj_id' => $project->obj_id,
                    'build_worker_obj_id' => $worker->obj_id,
                    'build_release_request_obj_id' => $releaseRequest->obj_id,
                    'message' => $message,
                ])->critical()->save();
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
                    'obj_status_did' => \ServiceBase_IHasStatus::STATUS_DELETED,
                ],
                "project_obj_id=:id",
                [
                    ':id' => $releaseRequest->rr_project_obj_id,
                ]
            );

            ToolJob::updateAll(
                [
                    'obj_status_did' => \ServiceBase_IHasStatus::STATUS_ACTIVE,
                ],
                'project_obj_id=:id AND "version"=:version',
                [
                    ':id' => $releaseRequest->rr_project_obj_id,
                    ':version' => $releaseRequest->rr_build_version,
                ]
            );

            // an: Асинхронная задача на email/sms/... оповещение
            $notificationItem = new JiraNotificationQueue();
            $notificationItem->jnq_project_obj_id = $project->obj_id;
            $notificationItem->jnq_old_version = $oldVersion;
            $notificationItem->jnq_new_version = $message->version;
            if (!$notificationItem->save()) {
                $this->debugLogger->error("Can't send notification task: " . json_encode($notificationItem->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        $transaction->commit();

        //self::sendReleaseRequestUpdated($releaseRequest->obj_id);

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestCurrentStatusRequest $message
     * @param MessagingRdsMs                             $model
     */
    public function replyToCurrentStatusRequest(Message\ReleaseRequestCurrentStatusRequest $message, MessagingRdsMs $model)
    {
        $releaseRequest = ReleaseRequest::findByPk($message->releaseRequestId);

        // an: В любом случае что-то ответить нужно, даже если запрос релиза уже удалили. Иначе будет таймаут у системы, которые запросила данные
        $model->sendCurrentStatusReply(new Message\ReleaseRequestCurrentStatusReply($releaseRequest ? $releaseRequest->rr_status : null, $message->getUniqueTag()));

        $message->accepted();
    }

    /**
     * @param Message\ProjectsRequest $message
     * @param MessagingRdsMs          $model
     */
    public function actionReplyProjectsList(Message\ProjectsRequest $message, MessagingRdsMs $model)
    {
        $projects = Project::find()->all();
        $result = array();
        foreach ($projects as $project) {
            /** @var $project Project */
            $result[] = array(
                'name' => $project->project_name,
                'current_version' => $project->project_current_version,
            );
        }

        $model->sendGetProjectsReply(new Message\ProjectsReply($result));

        $message->accepted();
    }

    /**
     * @param Message\ProjectBuildsToDeleteRequest $message
     * @param MessagingRdsMs                       $model
     *
     * @throws CException
     */
    public function actionReplyProjectsBuildsToDelete(Message\ProjectBuildsToDeleteRequest $message, MessagingRdsMs $model)
    {
        $builds = $message->allBuilds;

        $result = array();
        foreach ($builds as $build) {
            if (!preg_match('~\d{2,3}\.\d\d\.\d+\.\d+~', $build['version']) && !preg_match('~2014\.\d{2,3}\.\d\d\.\d+\.\d+~', $build['version'])) {
                // an: неизвестный формат версии, лучше не будем удалять :) фиг его знает что это
                $this->debugLogger->error("Unknown version format: {$build['version']} (build={$build['project']}-{$build['version']})");
                continue;
            }
            /** @var $project Project */
            $project = Project::findByAttributes(['project_name' => $build['project']]);
            if (!$project) {
                // an: непонятно что и зачем это нам прислали, лучше не будем удалять
                $this->debugLogger->error("Unknown project: {$build['project']} (build={$build['project']}-{$build['version']})");
                continue;
            }

            if ($build['version'] == $project->project_current_version) {
                // an: Ну никак нельзя удалять ту версию, что сейчас зарелижена
                $this->debugLogger->message("Active project and version (build={$build['project']}-{$build['version']})");
                continue;
            }

            $releaseRequest = ReleaseRequest::findByAttributes([
                'rr_project_obj_id' => $project->obj_id,
                'rr_build_version' => $build['version'],
            ]);

            $interval = "-" . Yii::$app->params['garbageCollector']['minTimeAtProd'];
            if (empty($interval)) {
                $this->debugLogger->error("Empty interval at RDS garbage collector. Stop removing packets");
                break;
            }
            if ($releaseRequest && $releaseRequest->rr_last_time_on_prod > date('Y-m-d', strtotime($interval))) {
                // an: Не удаляем те билды, что были на проде меньше недели назад
                $this->debugLogger->message("Last time at prod less then `$interval` (build={$build['project']}-{$build['version']})");
                continue;
            }

            $numbersOfTest = explode(".", $build['version']);

            $numbersOfCurrent = explode(".", $project->project_current_version);

            if ($numbersOfCurrent[0] - 1 > $numbersOfTest[0] || $numbersOfCurrent[0] == $numbersOfTest[0]) {
                $count = $this->countInstalledBuildsBetweenVersions($project->obj_id, $build['version'], $project->project_current_version);

                if ($count > 10) {
                    // an: Нужно наличие минимум 10 версий от текущей, что бы было куда откатываться
                    $result[] = $build;
                    $this->debugLogger->message("(!) Removing build of current build (build={$build['project']}-{$build['version']})");
                } else {
                    $this->debugLogger->message("Projects of current version at prod=$count, less then 10 (build={$build['project']}-{$build['version']})");
                }
            } elseif ($numbersOfCurrent[0] - 1 == $numbersOfTest[0]) {
                $count = $this->countInstalledBuildsBetweenVersions($project->obj_id, $build['version'], $numbersOfCurrent[0] . ".00.000.000");

                if ($count > 5) {
                    // an: Нужно наличие минимум 2 версий в текущем релизе, что бы точно могли откатиться
                    $result[] = $build;
                    $this->debugLogger->message("(!) Removing build of previous build (build={$build['project']}-{$build['version']})");
                } else {
                    $this->debugLogger->message("Projects of previous version at prod=$count, less then 5 (build={$build['project']}-{$build['version']})");
                }
            }
        }

        $model->sendGetProjectBuildsToDeleteRequestReply(new Message\ProjectBuildsToDeleteReply($result));

        $message->accepted();
    }

    /**
     * @param int $projectId
     * @param string $startVersion
     * @param string $endVersion
     * @return int|string
     */
    public static function countInstalledBuildsBetweenVersions(int $projectId, string $startVersion, string $endVersion)
    {
        $count = ReleaseRequest::find()
            ->andWhere(['rr_project_obj_id' => $projectId])
            ->andWhere("string_to_array(rr_build_version, '.')::int[] > string_to_array('" . addslashes($startVersion) . "', '.')::int[]")
            ->andWhere("string_to_array(rr_build_version, '.')::int[] < string_to_array('" . addslashes($endVersion) . "', '.')::int[]")
            ->andWhere(['in', 'rr_status', [ReleaseRequest::STATUS_INSTALLED, ReleaseRequest::STATUS_OLD]])
            ->count();

        return $count;
    }

    /**
     * @param Message\RemoveReleaseRequest $message
     * @param MessagingRdsMs               $model
     *
     * @throws CDbException
     */
    public function actionRemoveReleaseRequest(Message\RemoveReleaseRequest $message, MessagingRdsMs $model)
    {
        $project = Project::findByAttributes(['project_name' => $message->projectName]);
        if (!$project) {
            $message->accepted();
            $this->debugLogger->message("Skipped removing release request $message->projectName-$message->version as project not exists");

            return;
        }

        $rr = ReleaseRequest::findByAttributes([
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $message->version,
        ]);

        if ($rr) {
            $this->debugLogger->message("Removed release request $message->projectName-$message->version");
            $rr->delete();
        }
        $this->debugLogger->message("Skipped removing release request $message->projectName-$message->version as not exists");

        $message->accepted();
    }

    /**
     * @param Message\ReleaseRequestMigrationStatus $message
     * @param MessagingRdsMs                        $model
     *
     * @throws CDbException
     * @throws Exception
     */
    public function actionSetMigrationStatus(Message\ReleaseRequestMigrationStatus $message, MessagingRdsMs $model)
    {
        $projectObj = Project::findByAttributes(array('project_name' => $message->project));

        if (!$projectObj) {
            $this->debugLogger->error('unknown project ' . $message->project);
            $message->accepted();

            return;
        }
        $releaseRequest = ReleaseRequest::findByAttributes(array('rr_build_version' => $message->version, 'rr_project_obj_id' => $projectObj->obj_id));

        if (!$releaseRequest) {
            $this->debugLogger->error('unknown release request: project=' . $message->project . ", build_version=" . $message->version);
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

        $text = json_encode(array('ok' => $releaseRequest->save()));
        $transaction->commit();

        \Cronjob_Tool_AsyncReader_Deploy::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param int $id
     */
    public static function sendReleaseRequestUpdated($id)
    {
        /** @var $debugLogger \ServiceBase_IDebugLogger */
        $debugLogger = \Yii::$app->debugLogger;

        if (!$releaseRequest = ReleaseRequest::findByPk($id)) {
            return;
        }

        $debugLogger->message("Sending to comet new data of releaseRequest $id");

        $html = \Yii::$app->view->renderFile('@app/views/site/_releaseRequestGrid.php', [
            'dataProvider' => $releaseRequest->search(['obj_id' => $id]),
        ]);

        \Yii::$app->webSockets->send('releaseRequestChanged', ['rr_id' => $id, 'html' => $html]);
        $debugLogger->message("Sended");
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
