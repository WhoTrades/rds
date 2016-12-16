<?php
class SystemController extends Controller
{
    public $pageTitle = 'Управление RDS';

    /**
     * @return array
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * @return array
     */
    public function accessRules()
    {
        return array(
            array('allow',
                'users' => array('@'),
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $config = RdsDbConfig::get();

        if (isset($_POST['deployment_enabled'])) {
            $config->deployment_enabled = (int) $_POST['deployment_enabled'];
            $config->save();

            Log::createLogMessage("Обновление серверов " . ($config->deployment_enabled ? "влючено" : "отключено"));

            Yii::app()->webSockets->send('deployment_status_changed', ['deployment_enabled' => $config->deployment_enabled]);

            $this->refresh();
        }

        return $this->render('index', [
            'config' => $config,
        ]);
    }
}
