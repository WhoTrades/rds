<?php
namespace app\controllers;

use app\models\Log;
use app\models\RdsDbConfig;
use app\models\forms\StopDeploymentForm;

class SystemController extends Controller
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
