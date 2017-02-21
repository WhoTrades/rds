<?php
namespace app\controllers;

use yii\web\HttpException;
use app\models\MaintenanceTool;

class MaintenanceToolController extends Controller
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

    public function actionIndex()
    {
        $model = new MaintenanceTool(['scenario' => 'search']);
        if(isset($_GET['MaintenanceTool']))
            $model->attributes=$_GET['MaintenanceTool'];

        return $this->render('index',array(
            'model'=>$model,
        ));
    }

    public function actionStart($id)
    {
        /** @var $tool MaintenanceTool */
        $tool = $this->loadModel($id);

        $mtr = $tool->start(\Yii::$app->user->getIdentity()->username);

        if (empty($mtr->errors)) {
            $this->redirect(['/maintenance-tool-run/view/', 'id' => $mtr->obj_id]);
        } else {
            throw new Exception("Can't  create new instance of tool: ".json_encode($mtr->errors));
        }
    }

    public function actionStop($id)
    {
        /** @var $tool MaintenanceTool */
        $tool = $this->loadModel($id);

        $tool->stop(\Yii::$app->user->getIdentity()->username);

        $this->redirect(['/maintenance-tool/']);
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return MaintenanceTool the loaded model
     * @throws HttpException
     */
    public function loadModel($id)
    {
        $model = MaintenanceTool::findByPk($id);
        if($model===null)
            throw new HttpException(404,'The requested page does not exist.');
        return $model;
    }
}