<?php
namespace whotrades\rds\controllers;

use whotrades\rds\components\ActiveRecord;
use whotrades\rds\components\Status;
use whotrades\rds\models\RdsDbConfig;
use whotrades\rds\models\ReleaseRequest;
use whotrades\rds\models\ReleaseReject;
use whotrades\rds\models\Project;
use whotrades\rds\models\Log;
use whotrades\rds\models\Build;
use whotrades\rds\services\NotificationServiceInterface;
use yii\web\HttpException;

class SiteController extends ControllerRestrictedBase
{
    public $pageTitle = 'Релизы и запреты';

    /**
     * @var NotificationServiceInterface
     */
    private $notificationService;

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
                    ['allow' => true, 'actions' => ['login', 'secret']],
                ],
            ],
        ];
    }

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
     * @return string
     */
    public function actionIndex()
    {
        if (!\Yii::$app->user->can('developer')) {
            return $this->render('index-restricted');
        }

        $releaseRequestSearchModel = new ReleaseRequest();
        if (isset($_GET['ReleaseRequest'])) {
            $releaseRequestSearchModel->attributes = $_GET['ReleaseRequest'];
        }

        $releaseRejectSearchModel = new ReleaseReject();
        if (isset($_GET['ReleaseReject'])) {
            $releaseRejectSearchModel->attributes = $_GET['ReleaseReject'];
        }
        $sql = "SELECT rr_project_obj_id, COUNT(*)
                FROM rds.release_request
                WHERE obj_created > NOW() - interval '3 month'
                AND rr_user_id=:user
                AND obj_status_did=:status
                GROUP BY 1
                ORDER BY 2 DESC
                LIMIT 5";

        $ids = \Yii::$app->db->createCommand($sql, [
            ':user' => \Yii::$app->user->id,
            ':status' => Status::ACTIVE,
        ])->queryColumn();

        $mainProjects = Project::find()->where(['in', 'obj_id', $ids])->all();

        return $this->render('index', array(
            'releaseRequestSearchModel' => $releaseRequestSearchModel,
            'releaseRejectSearchModel' => $releaseRejectSearchModel,
            'mainProjects' => $mainProjects,
            'deploymentEnabled' => RdsDbConfig::get()->deployment_enabled,
            'releaseRequest' => [
                'model' => new ReleaseRequest(),
            ],
        ));
    }

    /**
     * @return string
     *
     * @throws \yii\db\Exception
     */
    public function actionCreateRelease()
    {
        if (!\Yii::$app->user->can('developer')) {
            return $this->render('index-restricted');
        }

        if (isset($_POST['ReleaseRequest']) && RdsDbConfig::get()->deployment_enabled) {
            $user = \Yii::$app->user;
            $projectId = $_POST['ReleaseRequest']['rr_project_obj_id'];
            $comment = $_POST['ReleaseRequest']['rr_comment'];

            /** @var ReleaseRequest $lastReleaseRequest */
            $lastReleaseRequest = ReleaseRequest::getLastReleaseRequestByProjectId($projectId);
            if ($lastReleaseRequest && $lastReleaseRequest->canBeRecreated()) {
                $lastReleaseRequest->recreate(
                    $user->id,
                    $comment
                );
            } else {
                ReleaseRequest::create(
                    $projectId,
                    $_POST['ReleaseRequest']['rr_release_version'],
                    $user->id,
                    $comment
                );
            }

            $project = Project::findByPk($projectId);
            $this->notificationService->sendBuildStarted($project->project_name, $user->identity->username, $comment);

            \Yii::$app->webSockets->send('updateAllReleaseRequests', []);
        }

        $this->redirect(['index']);
    }

    /**
     * @param int $id
     *
     * @return string
     *
     * @throws HttpException
     * @throws \yii\db\Exception
     */
    public function actionRecreateRelease($id)
    {
        if (!\Yii::$app->user->can('developer')) {
            return $this->render('index-restricted');
        }

        /** @var ReleaseRequest $releaseRequest */
        $releaseRequest = ReleaseRequest::findByPk($id);
        if (!$releaseRequest->canBeRecreated()) {
            $status = $releaseRequest->rr_status;
            $buildVersion = $releaseRequest->rr_build_version;
            $lastBuildVersion = $releaseRequest->project->getLastVersion($releaseRequest->rr_release_version);

            throw new HttpException(
                500,
                "Release request can not be recreated, status={$status}, build_version={$buildVersion}, last_build_version={$lastBuildVersion}"
            );
        }

        if (!RdsDbConfig::get()->deployment_enabled) {
            throw new HttpException(500, 'Deployment disabled');
        }

        $releaseRequest->recreate(\Yii::$app->user->id);

        \Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $this->redirect(['index']);
    }

    /**
     * Страница создания запрета на релиз
     * @throws \Exception
     * @return string
     */
    public function actionCreateReleaseReject()
    {
        $model = new ReleaseReject();

        if (isset($_POST['ReleaseReject'])) {
            $projectNames = [];
            foreach ($_POST['ReleaseReject']['rr_project_obj_id'] as $projectId) {
                /** @var $project Project */
                $project = Project::findOne(['obj_id' => $projectId]);
                if (!$project) {
                    continue;
                }
                $model = new ReleaseReject();
                $model->rr_user_id = \Yii::$app->user->id;
                $model->rr_project_obj_id = $projectId;
                $model->rr_release_version = $_POST['ReleaseReject']['rr_release_version'];
                $model->rr_comment = $_POST['ReleaseReject']['rr_comment'];
                if ($model->save()) {
                    $projectNames[] = $project->project_name;
                }
            }
            $projects = implode(", ", $projectNames);
            Log::createLogMessage("Создан запрет релизов $projects");

            $this->notificationService->sendReleaseRequestForbidden($projects, $model->user->email, $model->rr_comment);

            \Yii::$app->webSockets->send('updateAllReleaseRejects', []);

            $this->redirect(array('index'));
        }

        return $this->render('createReleaseReject', array(
            'model' => $model,
        ));
    }

    /**
     * @param int $id
     *
     * @return string | null
     */
    public function actionInstallRelease($id)
    {
        /** @var ReleaseRequest $releaseRequest */
        $releaseRequest = ReleaseRequest::findByPk($id);
        if (!$releaseRequest->shouldBeInstalled()) {
            throw new HttpException(500, 'Wrong release request status');
        }

        $deployment_enabled = RdsDbConfig::get()->deployment_enabled;
        if (!$deployment_enabled) {
            throw new HttpException(500, 'Deployment disabled');
        }

        $releaseRequest->sendInstallTask();

        \Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        if (!empty($_GET['ajax'])) {
            return "using";
        }

        $this->redirect('/');
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     */
    public function actionDeleteReleaseRequest($id)
    {
        $model = ReleaseRequest::findByPk($id);
        if (!$model) {
            return;
        }

        $messageModel = (new \whotrades\RdsSystem\Factory())->getMessagingRdsMsModel();

        $transaction = $model->getDbConnection()->beginTransaction();

        /** @var ReleaseRequest $releaseRequest */
        foreach (array_merge($model->getReleaseRequests()->all(), [$model]) as $releaseRequest) {
            $stopped = false;
            foreach ($releaseRequest->builds as $build) {
                if (in_array($build->build_status, Build::getInstallingStatuses())) {
                    $releaseRequest->rr_status = ReleaseRequest::STATUS_CANCELLING;
                    $releaseRequest->save();

                    $messageModel->sendKillTask(
                        $build->worker->worker_name,
                        new \whotrades\RdsSystem\Message\KillTask(
                            $releaseRequest->project->project_name,
                            $build->obj_id
                        )
                    );

                    Log::createLogMessage("Отменен {$releaseRequest->getTitle()} на {$build->worker->worker_name}");
                    $stopped = true;
                }
            }

            if (!$stopped) {
                Log::createLogMessage("Удален {$releaseRequest->getTitle()}");
                $releaseRequest->delete();
            }
        }

        \Yii::$app->webSockets->send('updateAllReleaseRequests', []);

        $transaction->commit();

        $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     */
    public function actionDeleteReleaseReject($id)
    {
        $model = ReleaseReject::findByPk($id);
        if ($model) {
            $transaction = $model->getDbConnection()->beginTransaction();
            try {
                Log::createLogMessage("Удален {$model->getTitle()}");
                $model->delete();
                $transaction->commit();
                \Yii::$app->webSockets->send('updateAllReleaseRejects', []);
            } catch (\Exception $e) {
                $transaction->rollBack();
            }
        }

        if (!isset($_GET['ajax'])) {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
        }
    }

    /**
     * @param $id
     *
     * @return string
     *
     * @throws HttpException
     */
    public function actionViewMigrationError($id): string
    {
        $model = ReleaseRequest::findByPk($id);
        if (!$model) {
            throw new HttpException(404, 'The requested page does not exist.');
        }
        return $this->render('viewMigrationError', array(
            'model' => $model,
        ));
    }

    /**
     * выход
     */
    public function actionLogout()
    {
        \Yii::$app->user->logout();
        $this->redirect(\Yii::$app->homeUrl);
    }
}
