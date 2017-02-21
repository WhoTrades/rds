<?php
namespace app\controllers;

use app\models\Log;
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

        if ($releaseRequest->canByUsedImmediately()) {
            $slaveList = ReleaseRequest::findAllByAttributes([
                'rr_leading_id' => $releaseRequest->obj_id,
                'rr_status' => [ReleaseRequest::STATUS_INSTALLED, ReleaseRequest::STATUS_OLD],
            ]);
            $releaseRequest->sendUseTasks(\Yii::$app->user->getIdentity()->username);
            foreach ($slaveList as $slave) {
                /** @var $slave ReleaseRequest */
                $slave->sendUseTasks(\Yii::$app->user->getIdentity()->username);
            }
            if (!empty($_GET['ajax'])) {
                echo "using";

                return;
            } else {
                $this->redirect('/');
            }
        }

        $code1 = rand(pow(10, 2), pow(10, 3) - 1);
        $code2 = rand(pow(10, 2), pow(10, 3) - 1);
        $releaseRequest->rr_project_owner_code = $code1;
        $releaseRequest->rr_release_engineer_code = $code2;
        $releaseRequest->rr_project_owner_code_entered = false;
        $releaseRequest->rr_release_engineer_code_entered = true;
        $releaseRequest->rr_status = ReleaseRequest::STATUS_CODES;

        $text = "Code: %s. USE {$releaseRequest->project->project_name} v.{$releaseRequest->rr_build_version}";
        \Yii::$app->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}(\Yii::$app->user->phone, sprintf($text, $code1));

        if ($releaseRequest->save()) {
            Cronjob_Tool_AsyncReader_Deploy::sendReleaseRequestUpdated($releaseRequest->obj_id);

            $currentUsed = ReleaseRequest::findByAttributes([
                'rr_project_obj_id' => $releaseRequest->rr_project_obj_id,
                'rr_status' => ReleaseRequest::STATUS_USED,
            ]);
            if ($currentUsed) {
                Cronjob_Tool_AsyncReader_Deploy::sendReleaseRequestUpdated($currentUsed->obj_id);
            }

            Log::createLogMessage("CODES {$releaseRequest->getTitle()}");
        }

        $this->redirect(['/use/index', 'id' => $id]);
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
            $releaseRequest->rr_migration_status = \ReleaseRequest::MIGRATION_STATUS_UPDATING;
            $logMessage = "Запущены pre миграции {$releaseRequest->getTitle()}";
        } else {
            $releaseRequest->rr_post_migration_status = \ReleaseRequest::MIGRATION_STATUS_UPDATING;
            $logMessage = "Запущены post миграции {$releaseRequest->getTitle()}";
        }

        foreach ($releaseRequest->project->project2workers as $p2w) {
            /** @var Project2worker $p2w */
            $worker = $p2w->worker;
            (new RdsSystem\Factory(\Yii::$app->debugLogger))->
                getMessagingRdsMsModel()->
                sendMigrationTask(
                    $worker->worker_name,
                    new \RdsSystem\Message\MigrationTask(
                        $releaseRequest->project->project_name,
                        $releaseRequest->rr_build_version,
                        $type
                    )
                );
        }

        if ($releaseRequest->save()) {
            Cronjob_Tool_AsyncReader_Deploy::sendReleaseRequestUpdated($releaseRequest->obj_id);
            Log::createLogMessage($logMessage);
        }

        $this->redirect('/');
    }

    /**
     * Проверки смс кодов
     *
     * @param $model
     * @param $releaseRequest
     */
    private function checkReleaseCode(ReleaseRequest $model, $releaseRequest)
    {
        if ($model->rr_project_owner_code == $releaseRequest->rr_project_owner_code) {
            Log::createLogMessage("Введен правильный Project Owner код {$releaseRequest->getTitle()}");
            $releaseRequest->rr_project_owner_code_entered = true;
        } else {
            $model->addError('rr_project_owner_code', "Код не подошел");
        }
        if ($model->rr_release_engineer_code == $releaseRequest->rr_release_engineer_code) {
            Log::createLogMessage("Введен правильный Release Engineer код {$releaseRequest->getTitle()}");
            $releaseRequest->rr_release_engineer_code_entered = true;
        }
    }

    /**
     * @param int $id
     * Lists all models.
    */
    public function actionIndex($id)
    {
        $releaseRequest = $this->loadModel($id);
        if ($releaseRequest->rr_status != ReleaseRequest::STATUS_CODES) {
            $this->redirect('/');
        }

        $model = new ReleaseRequest(['scenario' => 'use']);
        if (isset($_POST['ReleaseRequest'])) {
            $model->attributes = $_POST['ReleaseRequest'];

            $deployment_enabled = RdsDbConfig::get()->deployment_enabled;
            if (!$deployment_enabled) {
                $model->addError('rr_project_owner_code', 'Обновление серверов временно отключено, причина: ' . RdsDbConfig::get()->deployment_enabled_reason);
            }
            // проверяем правильность ввода смс
            $this->checkReleaseCode($model, $releaseRequest);

            if (isset($_POST['ajax']) && $_POST['ajax'] == 'release-request-use-form') {
                \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

                return \yii\widgets\ActiveForm::validate($model);
            }

            if ($releaseRequest->rr_project_owner_code_entered && $deployment_enabled) {
                $releaseRequest->sendUseTasks(\Yii::$app->user->getIdentity()->username);

                $slaveList = ReleaseRequest::findAllByAttributes([
                    'rr_leading_id' => $releaseRequest->obj_id,
                    'rr_status' => [ReleaseRequest::STATUS_INSTALLED, ReleaseRequest::STATUS_OLD],
                ]);
                foreach ($slaveList as $slave) {
                    /** @var $slave ReleaseRequest */
                    $slave->sendUseTasks(\Yii::$app->user->getIdentity()->username);
                }
            }
            $releaseRequest->save();
            $this->redirect('/');
        }
        return $this->render('index', array(
            'model' => $model,
            'releaseRequest' => $releaseRequest,
        ));
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
