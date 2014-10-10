<?php
use RdsSystem\Message;
use \RdsSystem\Model\Rabbit\MessagingRdsMs;

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
        return array();
    }


    /**
     * Performs actual work
     */
    public function run(\Cronjob\ICronjob $cronJob)
    {
        $rdsSystem = new RdsSystem\Factory($this->debugLogger);
        $model  = $rdsSystem->getMessagingRdsMsModel();

        $model->readTaskStatusChanged(false, function(Message\TaskStatusChanged $message) use ($model) {
            $this->debugLogger->message("Received status changed message: ".json_encode($message));
            $this->actionSetStatus($message, $model);
        });

        $model->readBuildPatch(false, function(Message\ReleaseRequestBuildPatch $message) use ($model) {
            $this->debugLogger->message("Received build patch message: ".json_encode($message));
            $this->actionSetBuildPatch($message, $model);
        });

        $model->readMigrations(false, function(Message\ReleaseRequestMigrations $message) use ($model) {
            $this->debugLogger->message("Received migrations message: ".json_encode($message));
            $this->actionSetMigrations($message, $model);
        });

        $model->readCronConfig(false, function(Message\ReleaseRequestCronConfig $message) use ($model) {
            $this->debugLogger->message("Received cron config message: ".json_encode($message));
            $this->actionSetCronConfig($message, $model);
        });

        $model->readUseError(false, function(Message\ReleaseRequestUseError $message) use ($model) {
            $this->debugLogger->message("Received use error message: ".json_encode($message));
            $this->actionSetUseError($message, $model);
        });

        $model->readOldVersion(false, function(Message\ReleaseRequestOldVersion $message) use ($model) {
            $this->debugLogger->message("Received old version message: ".json_encode($message));
            $this->actionSetOldVersion($message, $model);
        });

        $model->readUsedVersion(false, function(Message\ReleaseRequestUsedVersion $message) use ($model) {
            $this->debugLogger->message("Received used version message: ".json_encode($message));
            $this->actionSetUsedVersion($message, $model);
        });

        $model->readCurrentStatusRequest(false, function(Message\ReleaseRequestCurrentStatusRequest $message) use ($model) {
            $this->debugLogger->message("Received request of release request status: ".json_encode($message));
            $this->replyToCurrentStatusRequest($message, $model);
        });

        $model->readMigrationStatus(false, function(Message\ReleaseRequestMigrationStatus $message) use ($model) {
            $this->debugLogger->message("Received request of release request status: ".json_encode($message));
            $this->actionSetMigrationStatus($message, $model);
        });

        $model->readGetProjectsRequest(false, function(Message\ProjectsRequest $message) use ($model) {
            $this->debugLogger->message("Received request of projects list: ".json_encode($message));
            $this->actionReplyProjectsList($message, $model);
        });

        $model->readGetProjectBuildsToDeleteRequest(false, function(Message\ProjectBuildsToDeleteRequest $message) use ($model) {
            $this->debugLogger->message("Received request of projects to delete: ".json_encode($message));
            $this->actionReplyProjectsBuildsToDelete($message, $model);
        });

        $model->readRemoveReleaseRequest(false, function(Message\RemoveReleaseRequest $message) use ($model) {
            $this->debugLogger->message("Received remove release request message: ".json_encode($message));
            $this->actionRemoveReleaseRequest($message, $model);
        });

        $model->readHardMigrationStatus(false, function(Message\HardMigrationStatus $message) use ($model) {
            $this->debugLogger->message("Received changing status of hard migration: ".json_encode($message));
            $this->actionUpdateHardMigrationStatus($message, $model);
        });

        $this->debugLogger->message("Start listening");

        $this->waitForMessages($model, $cronJob);
    }


    public function actionSetStatus(Message\TaskStatusChanged $message, MessagingRdsMs $model)
    {
        $status = $message->status;
        $version = $message->version;
        $attach = $message->attach;

        /** @var $build Build*/
        $build = Build::model()->findByPk($message->taskId);
        if (!$build) {
            $this->debugLogger->error("Build $message->taskId not found, message=".json_encode($message));
            $message->accepted();
            return;
        }

        $project = $build->project;

        $build->build_status = $status;
        if ($attach) {
            $build->build_attach = $attach;
        }
        if ($version) {
            $build->build_version = $version;
        }

        $build->save();

        switch ($status) {
            case Build::STATUS_INSTALLED:
                if ($build->releaseRequest && $build->releaseRequest->countNotFinishedBuilds() == 0) {
                    $builds = $build->releaseRequest->builds;
                    $build->releaseRequest->rr_status = \ReleaseRequest::STATUS_INSTALLED;
                    $build->releaseRequest->rr_built_time = date("r");
                    $build->releaseRequest->save();
                    $title = "Success installed $project->project_name v.$version";
                    $text = "Проект $project->project_name был собран и разложен по серверам.<br />";
                    foreach ($builds as $val) {
                        $text .= "<a href='".Yii::app()->createAbsoluteUrl('build/view', array('id' => $val->obj_id))."'>Подробнее {$val->worker->worker_name} v.{$val->build_version}</a><br />";
                    }

                    foreach (Yii::app()->params['jiraProjects'] as $jiraProject) {
                        $jiraVersion = new JiraCreateVersion();
                        $jiraVersion->attributes = [
                                'jira_name' => $project->project_name."-".$build->releaseRequest->rr_build_version,
                                'jira_description' => 'Сборка #'.$build->build_release_request_obj_id.', '.$build->releaseRequest->rr_user.' [auto]',
                                'jira_project' => $jiraProject,
                                'jira_archived' => true,
                                'jira_released' => false,
                        ];

                        $jiraVersion->save(false);
                    }

                    Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseRejectCustomNotification'}('success', $title, $version, $text);
                    foreach (explode(",", \Yii::app()->params['notify']['status']['phones']) as $phone) {
                        if (!$phone) continue;
                        Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                    }
                }
                break;
            case Build::STATUS_FAILED:
                $title = "Failed to install $project->project_name";
                $text = "Проект $project->project_name не удалось собрать. <a href='".Yii::app()->createAbsoluteUrl('build/view', array('id' => $build->obj_id))."'>Подробнее</a>";

                Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseRejectCustomNotification'}('success', $title, $version, $text);
                foreach (explode(",", \Yii::app()->params['notify']['status']['phones']) as $phone) {
                    if (!$phone) continue;
                    Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                }
                break;
            case Build::STATUS_CANCELLED:
                $title = "Failed to install $project->project_name";
                $text = "Проект $project->project_name не удалось собрать. <a href='".Yii::app()->createAbsoluteUrl('build/view', array('id' => $build->obj_id))."'>Подробнее</a>";

                $c = new CDbCriteria(array(
                        'with' => array('project', 'project.project2workers', 'builds'),
                ));
                $c->compare('project2workers.worker_obj_id', $build->build_worker_obj_id);
                $c->compare('rr_status', array(\ReleaseRequest::STATUS_CANCELLING));
                $c->compare('build_status', array(\Build::STATUS_BUILDING, \Build::STATUS_BUILT));
                $task = \ReleaseRequest::model()->find($c);
                if (!$task && $build->releaseRequest) {
                    $releaseRequest = $build->releaseRequest;
                    $releaseRequest->rr_status = \ReleaseRequest::STATUS_CANCELLED;
                    $releaseRequest->save();
                }

                Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseRejectCustomNotification'}('success', $title, $version, $text);
                foreach (explode(",", \Yii::app()->params['notify']['status']['phones']) as $phone) {
                    if (!$phone) continue;
                    Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
                }
                break;
        }

        $this->sendReleaseRequestUpdated($build->build_release_request_obj_id);

        $message->accepted();
    }

    public function actionSetBuildPatch(Message\ReleaseRequestBuildPatch $message, MessagingRdsMs $model)
    {
        if (!$Project = Project::model()->findByAttributes(['project_name' => $message->project])) {
            $this->debugLogger->error("Project $message->project not found");
            $message->accepted();

            return;
        }

        $releaseRequest = \ReleaseRequest::model()->findByAttributes([
            'rr_project_obj_id' => $Project->obj_id,
            'rr_build_version' => $message->version,
        ]);

        if (!$releaseRequest) {
            $this->debugLogger->error("Release request of project=$message->project, version=$message->version not found");
            $message->accepted();

            return;
        }

        $lines = explode("\n", str_replace("\r", "", $message->output));

        foreach ($lines as $line) {
            if (preg_match('~^\s*(?<hash>\w+)\|(?<comment>.*)\|/(?<author>.*?)/$~', $line, $matches)) {
                $commit = new JiraCommit();
                if (preg_match_all('~#(WT\w-\d+)~', $matches['comment'], $ans)) {
                    foreach ($ans[1] as $val2) {
                        $commit->attributes = [
                            'jira_commit_build_tag' => $releaseRequest->getBuildTag(),
                            'jira_commit_hash' => $matches['hash'],
                            'jira_commit_author' => $matches['author'],
                            'jira_commit_comment' => $matches['comment'],
                            'jira_commit_ticket' => $val2,
                            'jira_commit_project' => explode('-', $val2)[0],
                        ];

                        $commit->save();
                    }
                }
            }
        }

        $this->sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    public function actionSetMigrations(Message\ReleaseRequestMigrations $message, MessagingRdsMs $model)
    {
        /** @var $project Project */
        $project = Project::model()->findByAttributes(['project_name' => $message->project]);
        if (!$project) {
            $this->debugLogger->error(404, 'Project not found');
            $message->accepted();
            return;
        }

        /** @var $releaseRequest ReleaseRequest */
        $releaseRequest = ReleaseRequest::model()->findByAttributes(array(
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
                $hm = new HardMigration();
                $hm->attributes = [
                    'migration_release_request_obj_id' => $releaseRequest->obj_id,
                    'migration_type' => 'hard',
                    'migration_name' => $migration,
                    'migration_ticket' => str_replace('#', '', $ticket),
                    'migration_status' => HardMigration::MIGRATION_STATUS_NEW,
                ];
                if (!$hm->save()) {
                    if (count($hm->errors) != 1 || !isset($hm->errors["migration_name"])) {
                        $this->debugLogger->error("Can't save HardMigration: ".json_encode($hm->errors));
                    } else {
                        $this->debugLogger->message("Skip migration $migration as already exists in DB (".json_encode($hm->errors).")");
                    }
                }
            }
        }

        $releaseRequest->save(false);

        $this->sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    public function actionSetCronConfig(Message\ReleaseRequestCronConfig $message, MessagingRdsMs $model)
    {
        /** @var $build Build */
        $build = Build::model()->findByPk($message->taskId);

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

        $this->sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    public function actionSetUseError(Message\ReleaseRequestUseError $message, MessagingRdsMs $model)
    {
        $releaseRequest = \ReleaseRequest::model()->findByPk($message->releaseRequestId);
        if (!$releaseRequest) {
            $this->debugLogger->error("Release Request #$message->releaseRequestId not found");
            $message->accepted();
            return;
        }
        $releaseRequest->rr_use_text = $message->text;
        $releaseRequest->rr_status = \ReleaseRequest::STATUS_FAILED;
        $releaseRequest->save();

        $this->sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    public function actionSetOldVersion(Message\ReleaseRequestOldVersion $message, MessagingRdsMs $model)
    {
        $releaseRequest = \ReleaseRequest::model()->findByPk($message->releaseRequestId);

        if (!$releaseRequest) {
            $this->debugLogger->error("Release Request #$message->releaseRequestId not found");
            $message->accepted();
            return;
        }

        if (!$releaseRequest->rr_old_version) {
            $releaseRequest->rr_old_version = $message->version;
            $releaseRequest->save(false);

            $this->sendReleaseRequestUpdated($releaseRequest->obj_id);
        }

        $message->accepted();
    }

    public function actionSetUsedVersion(Message\ReleaseRequestUsedVersion $message, MessagingRdsMs $model)
    {
        $worker = Worker::model()->findByAttributes(array('worker_name' => $message->worker));
        if (!$worker) {
            $this->debugLogger->error("Worker $message->worker not found");
            $message->accepted();
            return;
        }

        if (!in_array($message->status, array(\ReleaseRequest::STATUS_USED, \ReleaseRequest::STATUS_USED_ATTEMPT))) {
            $this->debugLogger->error('Forbidden, invalid status '.$message->status);
            $message->accepted();
            return;
        }

        $project = \Project::model()->findByAttributes(array('project_name' => $message->project));
        if (!$project) {
            $this->debugLogger->error("Project $message->project not found");
            $message->accepted();
            return;
        }

        $transaction = $project->dbConnection->beginTransaction();

        /** @var $releaseRequest ReleaseRequest */
        $releaseRequest = \ReleaseRequest::model()->findByAttributes(array(
            'rr_build_version' => $message->version,
            'rr_project_obj_id' => $project->obj_id,
        ));

        $builds = \Build::model()->findAllByAttributes(array(
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
            $build = \Build::model()->findByAttributes(array(
                'build_project_obj_id' => $project->obj_id,
                'build_worker_obj_id' => $worker->obj_id,
                'build_release_request_obj_id' => $releaseRequest->obj_id,
            ));
            $build->build_status = Build::STATUS_USED;
            $build->save();
        }

        $p2w = Project2worker::model()->findByAttributes(array(
            'worker_obj_id' => $worker->obj_id,
            'project_obj_id' => $project->obj_id,
        ));
        if ($p2w) {
            $p2w->p2w_current_version = $message->version;
            $p2w->save();
        }
        $list = \Project2worker::model()->findAllByAttributes(array(
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

            $oldUsed = \ReleaseRequest::model()->findByAttributes(array(
                'rr_status' => array(
                    \ReleaseRequest::STATUS_USED,
                    \ReleaseRequest::STATUS_USED_ATTEMPT,
                ),
                'rr_project_obj_id' => $project->obj_id,
            ));

            if ($oldUsed) {
                $oldUsed->rr_status = \ReleaseRequest::STATUS_OLD;
                $oldUsed->rr_last_time_on_prod = date("r");
                $oldUsed->rr_revert_after_time = null;
                $oldUsed->save(false);

                $this->sendReleaseRequestUpdated($oldUsed->obj_id);
            }

            if ($releaseRequest) {
                $releaseRequest->rr_status = $message->status;
                $releaseRequest->save(false);

                if ($message->status == \ReleaseRequest::STATUS_USED) {
                    $jiraUse = new JiraUse();
                    $jiraUse->attributes = [
                        'jira_use_from_build_tag' => $project->project_name.'-'.$oldVersion,
                        'jira_use_to_build_tag' => $releaseRequest->getBuildTag(),
                    ];
                    $jiraUse->save();
                }
            }

            if ($oldVersion < $message->version) {
                $title = "Deployed $project->project_name v.$message->version";
            } else {
                $title = "Reverted $project->project_name v.$message->version";
            }
            Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendReleaseReleased'}($project->project_name, $message->version);
            foreach (explode(",", \Yii::app()->params['notify']['use']['phones']) as $phone) {
                if (!$phone) continue;
                Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $title);
            }
        }

        $transaction->commit();

        $this->sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    public function replyToCurrentStatusRequest(Message\ReleaseRequestCurrentStatusRequest $message, MessagingRdsMs $model)
    {
        $releaseRequest = ReleaseRequest::model()->findByPk($message->releaseRequestId);

        //an: В любом случае что-то ответить нужно, даже если запрос релиза уже удалили. Иначе будет таймаут у системы, которые запросила данные
        $model->sendCurrentStatusReply(new Message\ReleaseRequestCurrentStatusReply($releaseRequest ? $releaseRequest->rr_status : null, $message->getUniqueTag()));

        $message->accepted();
    }

    public function actionSetMigrationStatus(Message\ReleaseRequestMigrationStatus $message, MessagingRdsMs $model)
    {
        $transaction = ReleaseRequest::model()->getDbConnection()->beginTransaction();
        $projectObj = Project::model()->findByAttributes(array('project_name' => $message->project));

        if (!$projectObj) {
            $this->debugLogger->error('unknown project '.$message->project);
            $message->accepted();
            return;
        }
        $releaseRequest = ReleaseRequest::model()->findByAttributes(array('rr_build_version' => $message->version, 'rr_project_obj_id' => $projectObj->obj_id));

        if (!$releaseRequest) {
            $this->debugLogger->error('unknown release request: project='.$message->project.", build_version=".$message->version);
            $message->accepted();
            return;
        }

        if ($message->type == 'pre') {
            $releaseRequest->rr_migration_status = $message->status;

            if ($message->status == \ReleaseRequest::MIGRATION_STATUS_UP) {
                $releaseRequest->rr_new_migration_count = 0;
                $c = new CDbCriteria();
                $c->compare('rr_build_version', "<=$message->version");
                $c->compare('rr_project_obj_id', $projectObj->obj_id);

                ReleaseRequest::model()->updateAll(array('rr_migration_status' => $message->status, 'rr_new_migration_count' => 0), $c);
            }
        } else {
            $releaseRequest->rr_post_migration_status = $message->status;

            if ($message->status == \ReleaseRequest::MIGRATION_STATUS_UP) {
                $c = new CDbCriteria();
                $c->compare('rr_build_version', "<=$message->version");
                $c->compare('rr_project_obj_id', $projectObj->obj_id);

                ReleaseRequest::model()->updateAll(array('rr_migration_status' => $message->status), $c);
            }
        }

        $text = json_encode(array('ok' => $releaseRequest->save()));
        $transaction->commit();

        $this->sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    public function actionReplyProjectsList(Message\ProjectsRequest $message, MessagingRdsMs $model)
    {
        $projects = Project::model()->findAll();
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

    public function actionReplyProjectsBuildsToDelete(Message\ProjectBuildsToDeleteRequest $message, MessagingRdsMs $model)
    {
        $builds = $message->allBuilds;

        $result = array();
        foreach ($builds as $build) {
            if (!preg_match('~\d{2,3}\.\d\d\.\d+\.\d+~', $build['version']) && !preg_match('~2014\.\d{2,3}\.\d\d\.\d+\.\d+~', $build['version'])) {
                //an: неизвестный формат версии, лучше не будем удалять :) фиг его знает что это
                continue;
            }
            /** @var $project Project */
            $project = Project::model()->findByAttributes(['project_name' => $build['project']]);
            if (!$project) {
                //an: непонятно что и зачем это нам прислали, лучше не будем удалять
                continue;
            }

            if ($build['version'] == $project->project_current_version) {
                //an: Ну никак нельзя удалять ту версию, что сейчас зарелижена
                continue;
            }

            $releaseRequest = \ReleaseRequest::model()->findByAttributes([
                'rr_project_obj_id' => $project->obj_id,
                'rr_build_version' => $build['version'],
            ]);

            if ($releaseRequest && $releaseRequest->rr_last_time_on_prod > date('Y-m-d', strtotime('-1 week'))) {
                //an: Не удаляем те билды, что были на проде меньше месяца назад
                continue;
            }

            $numbersOfTest = explode(".", $build['version']);
            if ($numbersOfTest[0] == 2014) array_shift($numbersOfTest);

            $numbersOfCurrent = explode(".", $project->project_current_version);
            if ($numbersOfCurrent[0] == 2014) array_shift($numbersOfCurrent);

            if ($numbersOfCurrent[0] - 1 > $numbersOfTest[0]) {
                //an: если релиз отличается на 1 и больше от того что сейчас на проде, тогда удаляем
                $c = new CDbCriteria();
                $c->compare('rr_project_obj_id', $project->obj_id);
                $c->compare('rr_build_version', '>'.$build['version']);
                $c->compare('rr_build_version', '<'.$project->project_current_version);
                $count = \ReleaseRequest::model()->count($c);

                if ($count > 5) {
                    //an: Нужно наличие минимум 2 версий от текущей, что бы было куда откатываться
                    $result[] = $build;
                }
            }
        }

        Yii::import('application.controllers.StatisticController');
        CoreLight::getInstance()->getServiceBaseCacheKvdpp()->set(StatisticController::LAST_PACKAGE_REMOVE_CALL_TIME_KEY, time());

        $model->sendGetProjectBuildsToDeleteRequestReply(new Message\ProjectBuildsToDeleteReply($result));

        $message->accepted();
    }

    public function actionRemoveReleaseRequest(Message\RemoveReleaseRequest $message, MessagingRdsMs $model)
    {
        $project = Project::model()->findByAttributes(['project_name' => $message->projectName]);
        if (!$project) {
            $message->accepted();
            $this->debugLogger->message("Skipped removing release request $message->projectName-$message->version as project not exists");;
            return;
        }

        $rr = ReleaseRequest::model()->findByAttributes([
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $message->version,
        ]);

        if ($rr) {
            $this->debugLogger->message("Removed release request $message->projectName-$message->version");;
            $rr->delete();
        }
        $this->debugLogger->message("Skipped removing release request $message->projectName-$message->version as not exists");;

        $message->accepted();
    }

    public function actionUpdateHardMigrationStatus(Message\HardMigrationStatus $message, MessagingRdsMs $model)
    {
        /** @var $migration HardMigration */
        $migration = HardMigration::model()->findByAttributes(['migration_name' => $message->migration]);

        if (!$migration) {
            $this->debugLogger->error("Can't find migration $message->migration");
            $message->accepted();
            return;
        }

        $migration->migration_status = $message->status;
        $migration->migration_log = $message->text;
        $migration->save(false);

        $this->sendHardMigrationUpdated($migration->obj_id);
        $message->accepted();
    }

    private function sendReleaseRequestUpdated($id)
    {
        $this->debugLogger->message("Sending to comet new data of releaseRequest $id");
        Yii::app()->assetManager->setBasePath('/tmp');
        Yii::app()->assetManager->setBaseUrl('/assets');
        Yii::app()->urlManager->setBaseUrl('');
        $filename = Yii::getPathOfAlias('application.views.site._releaseRequestRow').'.php';
        $rowTemplate = include($filename);

        list($controller, $action) = Yii::app()->createController('/');
        $controller->setAction($controller->createAction($action));
        Yii::app()->setController($controller);
        $model = ReleaseRequest::model();
        $model->obj_id = $id;
        $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(),'bootstrap.widgets.TbGridView', [
            'dataProvider'=>new CActiveDataProvider($model, $model->search()),
            'columns'=>$rowTemplate,
            'rowCssClassExpression' => function(){return 'rowItem';},
        ]);
        $widget->init();
        ob_start();
        $widget->run();
        $html = ob_get_clean();

        $comet = Yii::app()->realplexor;
        $comet->send('releaseRequestChanged', ['rr_id' => $id, 'html' => $html]);
        $this->debugLogger->message("Sended");
    }

    private function sendHardMigrationUpdated($id)
    {
        $this->debugLogger->message("Sending to comet new data of hard migration #$id");
        Yii::app()->assetManager->setBasePath('/tmp');
        Yii::app()->assetManager->setBaseUrl('/assets');
        Yii::app()->urlManager->setBaseUrl('');
        $filename = Yii::getPathOfAlias('application.views.hardMigration._hardMigrationRow').'.php';

        list($controller, $action) = Yii::app()->createController('/');
        $controller->setAction($controller->createAction($action));
        Yii::app()->setController($controller);
        $model = HardMigration::model();
        $model->obj_id = $id;
        $rowTemplate = include($filename);
        $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(),'bootstrap.widgets.TbGridView', [
            'dataProvider'=>new CActiveDataProvider($model, $model->search()),
            'columns'=>$rowTemplate,
            'rowCssClassExpression' => function(){return 'rowItem';},
        ]);
        $widget->init();
        ob_start();
        $widget->run();
        $html = ob_get_clean();
        $this->debugLogger->message("html code generated");

        $comet = Yii::app()->realplexor;
        $comet->send('hardMigrationChanged', ['rr_id' => $id, 'html' => $html]);
        $this->debugLogger->message("Sended");
    }

    public function createUrl($route, $params)
    {
        return Yii::app()->createUrl($route, $params);
    }
}
