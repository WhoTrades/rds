<?php
namespace app\controllers;

use app\models\Log;
use yii\web\HttpException;

class LogController extends Controller
{
    public $pageTitle = 'Журнал';

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
        echo $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $model = new Log('search');
        if (isset($_GET['Log'])) {
            $model->attributes = $_GET['Log'];
        }

        echo $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Log the loaded model
     */
    public function loadModel($id)
    {
        $model = Log::findByPk($id);
        if ($model === null) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }
}
