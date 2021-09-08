<?php

namespace whotrades\rds\controllers;

use whotrades\rds\models\forms\StopDeploymentForm;
use whotrades\rds\models\Log;
use whotrades\rds\models\RdsDbConfig;

class SystemController extends ControllerRestrictedBase
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $config = RdsDbConfig::get();
        if (!$config) {
            $config = new RdsDbConfig([]);
            $config->save();
        }

        $model = new StopDeploymentForm();

        if ($model->load(\Yii::$app->request->post())) {
            if ($model->validate()) {
                $config->deployment_enabled = $model->status;
                $config->deployment_enabled_reason = $model->reason;
                $config->save();

                if ($config->deployment_enabled) {
                    $title  = "Обновление серверов влючено";
                    $body   = "Теперь можно собирать, активировать сборки, синхронизировать конфигурацию.";
                    $reason = '';
                    $type   = 'success';
                } else {
                    $title  = "Обновление серверов отключено";
                    $body   = "Сборки проектов, активация сборок и синронизация конфигов временно отключена.";
                    $reason = 'Причина: ' . ($model->reason ?: 'неизвестно');
                    $type   = 'danger';
                }

                Log::createLogMessage($title . $reason);

                \Yii::$app->webSockets->send(
                    'popup_message',
                    [
                        'title' => $title,
                        'body'  => $body . ' ' . $reason,
                        'type'  => $type,
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
