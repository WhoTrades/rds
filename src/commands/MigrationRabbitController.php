<?php
/**
 * Controller for processing migration messages witch was sent via RabbitMQ from service-deploy
 *
 * @example php yii.php migration-rabbit/index
 */
namespace whotrades\rds\commands;

use Yii;
use whotrades\RdsSystem\Cron\RabbitListener;
use whotrades\RdsSystem\Message;
use whotrades\rds\models\Project;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\Migration;
use whotrades\rds\helpers\WebSockets as WebSocketsHelper;
use whotrades\rds\helpers\Migration as MigrationHelper;

class MigrationRabbitController extends RabbitListener
{
    /**
     * @return void
     */
    public function actionIndex()
    {
        $model  = $this->getMessagingModel();

        $model->readMigrations(false, function (Message\ReleaseRequestMigrations $message) use ($model) {
            Yii::info("Received migrations message: " . json_encode($message));
            $this->actionSetMigrations($message);
        });

        $model->readMigrationStatus(false, function (Message\MigrationStatus $message) use ($model) {
            Yii::info("env={$model->getEnv()}, Received request of release request status: " . json_encode($message));
            $this->actionSetMigrationStatus($message);
        });

        Yii::info("Start listening");

        $this->waitForMessages($model);
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
        $releaseRequest = ReleaseRequest::findByAttributes([
            'rr_project_obj_id' => $project->obj_id,
            'rr_build_version' => $message->version,
        ]);
        if (!$releaseRequest) {
            Yii::error('Release request not found');
            $message->accepted();

            return;
        }

        MigrationHelper::createOrUpdateListByCommand($message->migrations, $message->type, $message->command, $project, $releaseRequest);

        // ag: For backward compatibility after #WTA-2267
        if ($message->type === Migration::TYPE_PRE && in_array($message->command, [MigrateController::MIGRATION_COMMAND_NEW, MigrateController::MIGRATION_COMMAND_NEW_ALL])) {
            $releaseRequest->rr_new_migration_count = count($message->migrations);
            $releaseRequest->rr_new_migrations = json_encode($message->migrations);
            $releaseRequest->save(false);
        }

        WebSocketsHelper::sendReleaseRequestUpdated($releaseRequest->obj_id);

        $message->accepted();
    }

    /**
     * @param Message\MigrationStatus $message
     */
    private function actionSetMigrationStatus(Message\MigrationStatus $message)
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

        $transaction = Yii::$app->db->beginTransaction();

        // ag: For backward compatibility after #WTA-2267
        if (!$message->migrationName && $message->type === Migration::TYPE_PRE) {
            $releaseRequest->rr_migration_status = $message->status;

            if ($message->status === Message\MigrationStatus::STATUS_FAILED) {
                $releaseRequest->rr_migration_error = $message->result;
            }

            if ($message->status === Message\MigrationStatus::STATUS_SUCCESS) {
                $releaseRequest->rr_new_migration_count = 0;

                ReleaseRequest::updateAll(['rr_migration_status' => $message->status, 'rr_new_migration_count' => 0], 'rr_build_version <= :version AND rr_project_obj_id = :id', [
                    ':version'  => $message->version,
                    ':id'       => $projectObj->obj_id,
                ]);
            }

            $releaseRequest->save();

            WebSocketsHelper::sendReleaseRequestUpdated($releaseRequest->obj_id);
        }

        $transaction->commit();

        $migrationTypeId = Migration::getTypeIdByName($message->type);
        $migration = Migration::findByAttributes(
            [
                'migration_type' => $migrationTypeId,
                'migration_name' => $message->migrationName,
                'migration_project_obj_id' => $projectObj->obj_id,
            ]
        );

        if (!$migration) {
            Yii::error("Skip unknown {$message->type} migration: project={$message->project}, migration_name={$message->migrationName}");
            $message->accepted();

            return;
        }

        $transaction = Yii::$app->db->beginTransaction();

        switch ($message->status) {
            case Message\MigrationStatus::STATUS_SUCCESS:
                $migration->succeed();
                break;
            case Message\MigrationStatus::STATUS_FAILED:
                $migration->failed();
                break;
        }
        $migration->updateLog($message->result);

        WebSocketsHelper::sendMigrationUpdated($migration->obj_id);

        $transaction->commit();

        $message->accepted();
    }
}
