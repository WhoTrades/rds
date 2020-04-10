<?php
namespace whotrades\rds\controllers;

use Yii;
use whotrades\rds\commands\DeployController;
use whotrades\rds\models\Migration;
use whotrades\rds\models\Log;
use whotrades\rds\models\Project2worker;
use yii\web\HttpException;
use whotrades\rds\models\RdsDbConfig;
use whotrades\rds\models\ReleaseRequest;

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

        $releaseRequest->sendUseTasks(\Yii::$app->user->getIdentity()->username);

        \Yii::$app->webSockets->send('updateAllReleaseRequests', []);

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
        $releaseRequest->getOldReleaseRequest()->sendUseTasks(\Yii::$app->user->getIdentity()->username, false);

        /** @var ReleaseRequest $childReleaseRequest */
        foreach ($releaseRequest->getReleaseRequests()->all() as $childReleaseRequest) {
            $childReleaseRequest->getOldReleaseRequest()->sendUseTasks(\Yii::$app->user->getIdentity()->username, false);
        }

        \Yii::$app->webSockets->send('updateAllReleaseRequests', []);

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
            $this->redirect('/');
        }

        $releaseRequest->rr_migration_status = ReleaseRequest::MIGRATION_STATUS_UPDATING;
        $logMessage = "Запущены pre миграции {$releaseRequest->getTitle()}";

        foreach ($releaseRequest->project->project2workers as $p2w) {
            /** @var Project2worker $p2w */
            $worker = $p2w->worker;
            (new \whotrades\RdsSystem\Factory())->
                getMessagingRdsMsModel()->
                sendMigrationTask(
                    $worker->worker_name,
                    new \whotrades\RdsSystem\Message\MigrationTask(
                        $releaseRequest->project->project_name,
                        $releaseRequest->rr_build_version,
                        Migration::TYPE_PRE,
                        $releaseRequest->project->script_migration_up
                    )
                );
        }

        if ($releaseRequest->save()) {
            DeployController::sendReleaseRequestUpdated($releaseRequest->obj_id);
            Log::createLogMessage($logMessage);
        }

        $this->redirect('/');
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
