<?php

class HardMigrationController extends Controller
{
    public $pageTitle = 'Тяжелые миграции';
    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl'
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
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'users'=>array('@'),
            ),
            array('deny',  // deny all users
                'users'=>array('*'),
            ),
        );
    }

    public function actionIndex()
    {
        $model=new HardMigration('search');
        $model->unsetAttributes();  // clear any default values
        if(isset($_GET['HardMigration']))
            $model->attributes=$_GET['HardMigration'];

        $this->render('index',array(
            'model'=>$model,
        ));
    }

    public function actionStart($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Запущена {$migration->getTitle()}");

        if (!$migration->canBeStarted() && !$migration->canBeRestarted()) {
            throw new Exception("Invalid migration status");
        }

        $migration->migration_status = HardMigration::MIGRATION_STATUS_STARTED;
        $migration->save(false);

        (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel($migration->migration_environment)->sendHardMigrationTask(new \RdsSystem\Message\HardMigrationTask(
           $migration->migration_name, $migration->project->project_name, $migration->project->project_current_version
        ));

        $this->redirect('/hardMigration/index');
    }

    public function actionRestart($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Перезапущена {$migration->getTitle()}");
        $this->actionStart($id);
    }

    public function actionPause($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("ПОставлена на паузу {$migration->getTitle()}");
        $this->sendUnixSignalAndRedirect($id, HardMigrationBase::SIGNAL_PAUSE, HardMigration::MIGRATION_STATUS_PAUSED);
    }

    public function actionResume($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Снята с паузы {$migration->getTitle()}");
        $this->sendUnixSignalAndRedirect($id, HardMigrationBase::SIGNAL_RESUME);
    }

    public function actionStop($id)
    {
        $migration = $this->loadModel($id);
        Log::createLogMessage("Остановлена {$migration->getTitle()}");
        $this->sendUnixSignalAndRedirect($id, HardMigrationBase::SIGNAL_STOP);
    }

    private function sendUnixSignalAndRedirect($id, $signal, $newStatus = null)
    {
        $migration = $this->loadModel($id);

        (new RdsSystem\Factory(Yii::app()->debugLogger))->getMessagingRdsMsModel($migration->migration_environment)->sendUnixSignal(new \RdsSystem\Message\UnixSignal(
            $migration->migration_pid, $signal
        ));

        if ($newStatus) {
            HardMigration::model()->updateByPk($id, ['migration_status' => $newStatus]);
        }

        $this->redirect('/hardMigration/index');
    }

    public function actionLog($id)
    {
        $migration = $this->loadModel($id);

        $this->render('log', ['migration' => $migration]);
    }

    /**
     * @param $id
     * @return HardMigration
     */
    public function loadModel($id)
    {
        $model=HardMigration::model()->findByPk($id);
        if($model===null)
            throw new CHttpException(404,'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param HardMigration $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if(isset($_POST['ajax']) && $_POST['ajax']==='hard-migration-form')
        {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}