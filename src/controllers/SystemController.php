<?php
namespace app\controllers;

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

        $model = new StopDeploymentForm();
        $model->status = !$config->deployment_enabled;
        if (!empty($_POST['StopDeploymentForm'])) {
            $model->attributes = $_POST['StopDeploymentForm'];
            if ($model->validate()) {
                $config->deployment_enabled = $model->status;
                $config->deployment_enabled_reason = $model->reason;
                $config->save();

                $str = "Обновление серверов " . ($config->deployment_enabled ? "влючено" : "отключено") . ($model->reason ? ", причина: " . $model->reason : '');
                Log::createLogMessage($str);

                \Yii::$app->webSockets->send(
                    'deployment_status_changed',
                    [
                        'deployment_enabled' => $config->deployment_enabled,
                        'reason' => $model->reason,
                    ]
                );

                $this->refresh();
            }
        }

        return $this->render('index', [
            'config' => $config,
            'model' => $model,
        ]);
    }
}
