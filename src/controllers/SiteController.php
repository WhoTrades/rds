<?php

class SiteController extends Controller
{
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
                'actions' => array('login'),
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

	public function actionIndex()
	{
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

        if(isset($_POST['ReleaseRequest']))
        {
            $model->attributes=$_POST['ReleaseRequest'];
            $model->rr_user = \Yii::app()->user->name;
            if($model->save()) {
                $list = Project2worker::model()->findAllByAttributes(array(
                    'project_obj_id' => $model->rr_project_obj_id,
                ));
                foreach ($list as $val) {
                    /** @var $val Project2worker */
                    $task = new Build();
                    $task->build_release_request_obj_id = $model->obj_id;
                    $task->build_worker_obj_id = $val->worker_obj_id;
                    $task->build_project_obj_id = $val->project_obj_id;
                    $task->save();
                }

                Yii::app()->whotrades->{'getMailingSystemFactory.getPhpLogsNotificationModel.sendRdsReleaseRequestNotification'}($model->rr_user, $model->project->project_name, $model->rr_comment);
                $text = "{$model->rr_user} requested {$model->project->project_name}. {$model->rr_comment}";
                foreach (explode(",", \Yii::app()->params['notify']['releaseRequest']['phones']) as $phone) {
                    Yii::app()->whotrades->{'getFinamTenderSystemFactory.getSmsSender.sendSms'}($phone, $text);
                }
                $this->redirect(array('index'));
            }
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
                $text = "{$model->rr_user} rejected {$model->project->project_name}. {$model->rr_comment}";
                foreach (explode(",", \Yii::app()->params['notify']['releaseReject']['phones']) as $phone) {
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
        if($model)
            $model->delete();

        if(!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    public function actionDeleteReleaseReject($id)
    {
        $model=ReleaseReject::model()->findByPk($id);
        if($model)
            $model->delete();

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

	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}
}
