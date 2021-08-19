<?php
namespace whotrades\rds\controllers;

use Yii;
use whotrades\rds\models\Migration;
use whotrades\rds\models\Log;
use yii\web\HttpException;
use whotrades\rds\models\RdsDbConfig;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\helpers\WebSockets as WebSocketsHelper;

class UseController extends ControllerRestrictedBase
{
    /**
     * @param int $id
     * Lists all models.
     * @return string
     */
    public function actionCreate($id)
    {
        $releaseRequest = $this->loadModel($id);
        if (!$releaseRequest->canBeUsed()) {
            throw new HttpException(500, 'Wrong release request status');
        }

        $deployment_enabled = RdsDbConfig::get()->deployment_enabled;
        if (!$deployment_enabled) {
            throw new HttpException(500, 'Deployment disabled');
        }

        $releaseRequest->sendUseTasks(Yii::$app->user->getIdentity()->username);

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        if (!empty($_GET['ajax'])) {
            return "using";
        } else {
            $this->redirect('/');
        }
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function actionRevert($id)
    {
        $releaseRequest = $this->loadModel($id);
        $releaseRequest->getOldReleaseRequest()->sendUseTasks(Yii::$app->user->getIdentity()->username, false);

        /** @var ReleaseRequest $childReleaseRequest */
        foreach ($releaseRequest->getReleaseRequests()->all() as $childReleaseRequest) {
            $childReleaseRequest->getOldReleaseRequest()->sendUseTasks(Yii::$app->user->getIdentity()->username, false);
        }

        Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        if (!empty($_GET['ajax'])) {
            return "using";
        } else {
            $this->redirect('/');
        }
    }

    /**
     * @param int $id
     *
     * @throws HttpException
     * @throws \Exception
     */
    public function actionMigrate($id)
    {
        $releaseRequest = $this->loadModel($id);
        if (!$releaseRequest->shouldBeMigrated()) {
            return $this->redirect('/');
        }

        $migrationList = Migration::findWithoutLog()
            ->andWhere([
                'migration_type' => Migration::TYPE_ID_PRE,
                'migration_project_obj_id' => $releaseRequest->project->obj_id,
            ])
            ->andWhere(['IN', 'obj_status_did', [Migration::STATUS_PENDING, Migration::STATUS_FAILED_APPLICATION]])
            ->andWhere(['<=', 'migration_release_request_obj_id', $releaseRequest->obj_id])
            ->orderBy(['migration_name' => SORT_ASC])
            ->all();

        if (!$migrationList) {
            Yii::info("No pending migrations for {$releaseRequest->getBuildTag()}");

            $releaseRequest->rr_migration_status = ReleaseRequest::MIGRATION_STATUS_UP;
            $releaseRequest->rr_new_migration_count = 0;
            $releaseRequest->save();

            WebSocketsHelper::sendReleaseRequestUpdated($releaseRequest->obj_id);

            return $this->redirect('/');
        }

        $releaseRequest->rr_migration_status = ReleaseRequest::MIGRATION_STATUS_UPDATING;
        $releaseRequest->rr_new_migration_count = count($migrationList);
        $releaseRequest->save();

        /** @var Migration $migration */
        foreach ($migrationList as $migration) {
            $migration->apply($releaseRequest);
        }

        WebSocketsHelper::sendReleaseRequestUpdated($releaseRequest->obj_id);
        Log::createLogMessage("Migrations of {$releaseRequest->getBuildTag()} are run");

        return $this->redirect('/');
    }

    /**
     * @param int $id
     *
     * @return ReleaseRequest
     * @throws HttpException
     */
    public function loadModel($id)
    {
        $model = ReleaseRequest::findByPk($id);
        if ($model == null) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }
}
