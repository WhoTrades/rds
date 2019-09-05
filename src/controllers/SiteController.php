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
use yii\web\HttpException;

class SiteController extends ControllerRestrictedBase
{
    public $pageTitle = 'Релизы и запреты';

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
            try {
                $transaction = ActiveRecord::getDb()->beginTransaction();

                $releaseRequestList = ReleaseRequest::create(
                    $_POST['ReleaseRequest']['rr_project_obj_id'],
                    $_POST['ReleaseRequest']['rr_release_version'],
                    \Yii::$app->user->id,
                    $_POST['ReleaseRequest']['rr_comment']
                );

                $transaction->commit();

                /** @var ReleaseRequest $releaseRequest */
                foreach ($releaseRequestList as $releaseRequest) {
                    $releaseRequest->sendBuildTasks();
                }

                \Yii::$app->webSockets->send('updateAllReleaseRequests', []);
            } catch (\Exception $e) {
                if ($transaction->isActive) {
                    $transaction->rollBack();
                }

                throw $e;
            }
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
            throw new HttpException(500, 'Release request can not be recreated');
        }

        if (!RdsDbConfig::get()->deployment_enabled) {
            throw new HttpException(500, 'Deployment disabled');
        }

        try {
            $transaction = ActiveRecord::getDb()->beginTransaction();

            $releaseRequestList = $releaseRequest->recreate(\Yii::$app->user->id);

            $transaction->commit();

            /** @var ReleaseRequest $releaseRequest */
            foreach ($releaseRequestList as $releaseRequest) {
                $releaseRequest->sendBuildTasks();
            }

            \Yii::$app->webSockets->send('updateAllReleaseRequests', []);
        } catch (\Exception $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            throw $e;
        }

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
            foreach (explode(",", \Yii::$app->params['notify']['releaseReject']['phones']) as $phone) {
                if (!$phone) {
                    continue;
                }
                $text = "{$model->user->email} rejected $projects. {$model->rr_comment}";
                \Yii::$app->smsSender->sendSms($phone, $text);
            }
            \Yii::$app->EmailNotifier->sendRdsReleaseRejectNotification(
                $model->user->email,
                $projects,
                $model->rr_comment
            );

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
     * выход
     */
    public function actionLogout()
    {
        \Yii::$app->user->logout();
        $this->redirect(\Yii::$app->homeUrl);
    }
}
