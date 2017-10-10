<?php
namespace app\controllers;

use app\commands\DeployController;
use Yii;
use app\models\Log;
use app\models\Project2worker;
use yii\web\HttpException;
use app\models\RdsDbConfig;
use app\models\ReleaseRequest;

class UseController extends Controller
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

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

        $slaveList = ReleaseRequest::findAllByAttributes([
            'rr_leading_id' => $releaseRequest->obj_id,
            'rr_status' => [ReleaseRequest::STATUS_INSTALLED, ReleaseRequest::STATUS_OLD],
        ]);
        $releaseRequest->sendUseTasks(\Yii::$app->user->getIdentity()->username);
        foreach ($slaveList as $slave) {
            /** @var $slave ReleaseRequest */
            $slave->sendUseTasks(\Yii::$app->user->getIdentity()->username);
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
     * @param string $type
     *
     * @throws HttpException
     * @throws \Exception
     */
    public function actionMigrate($id, $type)
    {
        $releaseRequest = $this->loadModel($id);
        if (!$releaseRequest->canBeUsed()) {
            $this->redirect('/');
        }

        if ($type == 'pre') {
            $releaseRequest->rr_migration_status = ReleaseRequest::MIGRATION_STATUS_UPDATING;
            $logMessage = "Запущены pre миграции {$releaseRequest->getTitle()}";
        } else {
            $releaseRequest->rr_post_migration_status = ReleaseRequest::MIGRATION_STATUS_UPDATING;
            $logMessage = "Запущены post миграции {$releaseRequest->getTitle()}";
        }

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
                        $type,
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
