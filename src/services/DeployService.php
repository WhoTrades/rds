<?php
declare(strict_types=1);

namespace whotrades\rds\services;

use whotrades\rds\components\Deploy\DeployEventInterface;
use whotrades\rds\components\Deploy\GenericEvent;
use whotrades\rds\models\Build;
use whotrades\rds\models\JiraUse;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Worker;
use whotrades\RdsSystem\Message\Base;
use whotrades\RdsSystem\Message\ReleaseRequestCronConfig;
use whotrades\RdsSystem\Message\ReleaseRequestUsedVersion;
use Yii;
use yii\base\Event;
use yii\db\Connection;
use yii\db\Exception;

class DeployService implements DeployServiceInterface
{

    /**
     * @param ReleaseRequestCronConfig $message
     *
     * @throws Exception
     */
    public function setCronConfig(ReleaseRequestCronConfig $message): void
    {
        $event = $this->createEvent($message);

        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_BEFORE, $event);

        $connection = $this->getDatabaseConnection();
        $transaction = $connection->beginTransaction();
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

            $event->releaseRequest = $releaseRequest;
            $event->build = $build;

            Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_PRE_COMMIT_HOOK, $event);

            $transaction->commit();
            $message->accepted();

            Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_AFTER, $event);
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param ReleaseRequestUsedVersion $message
     *
     * @throws Exception
     */
    public function setUsedVersion(ReleaseRequestUsedVersion $message): void
    {
        $event = $this->createEvent($message);

        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_BEFORE, $event);

        $worker = Worker::findByAttributes(array('worker_name' => $message->worker));
        if (!$worker) {
            Yii::error("Skip message. Worker $message->worker not found");
            $message->accepted();

            return;
        }

        /** @var $project Project */
        $project = Project::findByAttributes(array('project_name' => $message->project));
        if (!$project) {
            Yii::error("Skip message. Project $message->project not found");
            $message->accepted();

            return;
        }

        /** @var $releaseRequest ReleaseRequest */
        $releaseRequest = ReleaseRequest::findByAttributes([
            'rr_build_version' => $message->version,
            'rr_project_obj_id' => $project->obj_id,
        ]);

        if (!$releaseRequest) {
            Yii::error("Skip message. ReleaseRequest {$project->project_name}-{$message->version} not found");
            $message->accepted();

            return;
        }

        $build = Build::findByAttributes([
            'build_project_obj_id' => $project->obj_id,
            'build_worker_obj_id' => $worker->obj_id,
            'build_release_request_obj_id' => $releaseRequest->obj_id,
        ]);

        if (!$build) {
            Yii::error("Skip message. Build of releaseRequest {$project->project_name}-{$message->version} for worker {$worker->worker_name} not found");
            Yii::$app->sentry->captureMessage('unknown_build_info', [
                'build_project_obj_id' => $project->obj_id,
                'build_worker_obj_id' => $worker->obj_id,
                'build_release_request_obj_id' => $releaseRequest->obj_id,
                'message' => $message,
            ]);
            $message->accepted();

            return;
        }

        $connection = $this->getDatabaseConnection();
        $transaction = $connection->beginTransaction();

        $build->build_status = Build::STATUS_USED;
        $build->build_attach .= "\n\n=== Begin Use Log ===\n\n";
        $build->build_attach .= $message->text;
        $build->build_attach .= "\n\n=== End Use Log ===";
        $build->save();

        foreach ($releaseRequest->builds as $build) {
            if ($build->build_status != Build::STATUS_USED) {
                Yii::info("Some builds of releaseRequest {$project->project_name}-{$message->version} are not in USED status");
                Yii::info("Waiting for them...");

                $transaction->commit();
                $message->accepted();

                return;
            }
        }

        $oldVersion = $project->project_current_version;
        $project->updateCurrentVersion($message->version);

        $oldUsed = ReleaseRequest::getUsedReleaseByProjectId($project->obj_id);
        if ($oldUsed) {
            $oldUsed->rr_status = ReleaseRequest::STATUS_OLD;
            $oldUsed->rr_last_time_on_prod = date("r");
            $oldUsed->rr_revert_after_time = null;
            $oldUsed->save(false);

            foreach ($oldUsed->builds as $build) {
                /** @var $build Build */
                $build->build_status = Build::STATUS_INSTALLED;
                $build->save();
            }
        }

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

        $releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_USING_SUCCESS);

        $event->projectOldVersion = $oldVersion;
        $event->build = $build;
        $event->project = $project;
        $event->releaseRequest = $releaseRequest;

        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_PRE_COMMIT_HOOK, $event);

        $transaction->commit();
        $message->accepted();

        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_AFTER, $event);
    }

    /**
     * TODO: accept connection as DI
     *
     * @return Connection
     */
    public function getDatabaseConnection(): Connection
    {
        return Yii::$app->db;
    }

    /**
     * @param Base $message
     * @return GenericEvent
     */
    protected function createEvent(Base $message): GenericEvent
    {
        $event = new GenericEvent();
        $event->message = $message;
        return $event;
    }
}