<?php

class ReleaseVersionController extends Controller
{
    public $pageTitle = 'Версии';
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new ReleaseVersion;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['ReleaseVersion']))
		{
			$model->attributes=$_POST['ReleaseVersion'];
			if($model->save()) {
                $this->updateVersionsAtTeamCity();
				$this->redirect(array('view','id'=>$model->obj_id));
            }
		}


		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['ReleaseVersion']))
		{
			$model->attributes=$_POST['ReleaseVersion'];
			if($model->save()) {
                $this->updateVersionsAtTeamCity();
				$this->redirect(array('view','id'=>$model->obj_id));
            }
		}


		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

        $this->updateVersionsAtTeamCity();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		return $this->actionAdmin();
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new ReleaseVersion('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['ReleaseVersion']))
			$model->attributes=$_GET['ReleaseVersion'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ReleaseVersion the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=ReleaseVersion::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ReleaseVersion $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='release-version-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

    private function updateVersionsAtTeamCity()
    {
        if (!Yii::app()->params['syncVersionsWithCi']) {
            return;
        }

        $versions = [
            "+:refs/heads/(master)",
            "+:refs/heads/(feature/*)",
        ];
        foreach (ReleaseVersion::model()->findAll() as $version) {
            /** @var $version ReleaseVersion */
            $versions[] = "+:refs/heads/(release-$version->rv_version)";
        }

        $client = new \TeamcityClient\WtTeamCityClient();

        foreach ($client->getVcsRootsList() as $vcsRoot) {
            $client->updateVcsRootProperty($vcsRoot['id'], 'teamcity:branchSpec', implode("\n", $versions));
        }
    }
}
