<?php
namespace app\controllers;

use yii\web\HttpException;
use app\models\MaintenanceToolRun;

class MaintenanceToolRunController extends Controller
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
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        return $this->render('view',array(
            'model'=>$this->loadModel($id),
        ));
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $model = new MaintenanceToolRun(['scenario' => 'search']);
        if(isset($_GET['MaintenanceToolRun']))
            $model->attributes=$_GET['MaintenanceToolRun'];

        return $this->render('index',array(
            'model'=>$model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id)
    {
        $model = MaintenanceToolRun::findByPk($id);
        if($model===null)
            throw new HttpException(404,'The requested page does not exist.');
        return $model;
    }
}