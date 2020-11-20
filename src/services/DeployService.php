<?php
declare(strict_types=1);

namespace whotrades\rds\services;

use whotrades\rds\components\Deploy\DeployEventInterface;
use whotrades\rds\events\Deploy\CronConfigAfterEvent;
use whotrades\rds\events\Deploy\CronConfigBeforeEvent;
use whotrades\rds\events\Deploy\CronConfigPreCommitEvent;
use whotrades\rds\events\Deploy\UsedVersionAfterEvent;
use whotrades\rds\events\Deploy\UsedVersionBeforeEvent;
use whotrades\rds\events\Deploy\UsedVersionPreCommitEvent;
use whotrades\rds\models\Build;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Worker;
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
        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_BEFORE, new CronConfigBeforeEvent($message));

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

            Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_PRE_COMMIT_HOOK, new CronConfigPreCommitEvent($message, $releaseRequest));

            $transaction->commit();
            $message->accepted();

            Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_CRON_CONFIG_AFTER, new CronConfigAfterEvent($message, $releaseRequest));
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
        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_BEFORE, new UsedVersionBeforeEvent($message));

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

        $oldVersion = (string) $project->project_current_version;
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

        $releaseRequest->addBuildTimeLog(ReleaseRequest::BUILD_LOG_USING_SUCCESS);

        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_PRE_COMMIT_HOOK, new UsedVersionPreCommitEvent($message, $releaseRequest, $oldVersion));

        $transaction->commit();
        $message->accepted();

        Event::trigger(DeployServiceInterface::class, DeployEventInterface::EVENT_USED_VERSION_AFTER, new UsedVersionAfterEvent($message, $releaseRequest, $oldVersion));
    }

    /**
     * TODO: pass connection as DI
     *
     * @return Connection
     */
    public function getDatabaseConnection(): Connection
    {
        return Yii::$app->db;
    }
}
