<?php

class LogController extends Controller
{
    public $layout = '//layouts/main';
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
     * Lists all models.
     */
    public function actionIndex()
    {
        $model=new Log('search');
        $model->unsetAttributes();  // clear any default values
        if(isset($_GET['Log']))
            $model->attributes=$_GET['Log'];

        $this->render('admin',array(
            'model'=>$model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Log the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        $model=Log::model()->findByPk($id);
        if($model===null)
            throw new CHttpException(404,'The requested page does not exist.');
        return $model;
    }
}