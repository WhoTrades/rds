<?php

class DeveloperController extends Controller
{
    public $layout = '/layouts/column2';
    public $pageTitle = 'Разработчики';
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
        $model=new Developer;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if(isset($_POST['Developer']))
        {
            $model->attributes=$_POST['Developer'];
            if($model->save()) {
                Log::createLogMessage("Создан ".$model->getTitle());
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

        if(isset($_POST['Developer']))
        {
            $oldTitle = $model->getTitle();
            $model->attributes=$_POST['Developer'];
            if($model->save()) {
                Log::createLogMessage("Изменен $oldTitle на ".$model->getTitle());
                $this->redirect(array('view','id'=>$model->obj_id));
            }

        }

        $this->render('update',array(
            'model'=>$model,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        $developer = $this->loadModel($id);
        $message = "Удален ".$developer->getTitle();
        $developer->delete();

        Log::createLogMessage($message);

        // if AJAX request (triggered by deletion via index grid view), we should not redirect the browser
        if(!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * Manages all models.
     */
    public function actionIndex()
    {
        $model=new Developer('search');
        $model->unsetAttributes();  // clear any default values
        if(isset($_GET['Developer']))
            $model->attributes=$_GET['Developer'];

        $this->render('index',array(
            'model'=>$model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Developer the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        $model=Developer::model()->findByPk($id);
        if($model===null)
            throw new CHttpException(404,'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Developer $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if(isset($_POST['ajax']) && $_POST['ajax']==='developer-form')
        {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}